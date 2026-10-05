<?php

namespace App\Services\Imports;

use App\Services\PlaceHistoryService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OverturePlaceTypeReviewService
{
    public const ALLOWED_TYPES = [
        'campground',
        'motorhome-pitch',
        'camping-outdoor',
        'service-station',
    ];

    public function exportCreated(string $path): array
    {
        $sourceId = DB::table('external_sources')
            ->where('slug', OverturePlacesCsvStageService::SOURCE_SLUG)
            ->value('id');

        if (! $sourceId) {
            throw new RuntimeException('Overture-Quelle wurde nicht gefunden.');
        }

        $rows = DB::table('external_records as er')
            ->join('places as p', 'p.id', '=', 'er.place_id')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->where('er.external_source_id', $sourceId)
            ->where('er.status', 'active')
            ->where('er.classification', 'created')
            ->whereNotNull('er.place_id')
            ->where('p.is_active', true)
            ->orderBy('p.id')
            ->get([
                'er.external_id',
                'er.place_id',
                'er.normalized_data',
                'p.name',
                'pt.slug as current_place_type',
            ]);

        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Zielverzeichnis konnte nicht angelegt werden: '.$directory);
        }

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Exportdatei konnte nicht geöffnet werden: '.$path);
        }

        $headers = [
            'external_id',
            'place_id',
            'name',
            'current_place_type',
            'source_category',
            'basic_category',
            'confidence',
            'taxonomy_hierarchy',
            'taxonomy_alternates',
            'address',
            'city',
            'website',
            'phone',
            'email',
            'target_place_type',
            'reason',
        ];

        fputcsv($handle, $headers, ';', '"', '');

        foreach ($rows as $row) {
            $normalized = json_decode((string) $row->normalized_data, true) ?: [];
            $place = is_array($normalized['place'] ?? null) ? $normalized['place'] : [];
            $properties = is_array($normalized['source_properties'] ?? null) ? $normalized['source_properties'] : [];
            $address = is_array($place['address'] ?? null) ? $place['address'] : [];
            $operator = is_array($place['operator'] ?? null) ? $place['operator'] : [];

            fputcsv($handle, [
                (string) $row->external_id,
                (int) $row->place_id,
                (string) $row->name,
                (string) $row->current_place_type,
                (string) ($properties['overture_category'] ?? ''),
                (string) ($properties['basic_category'] ?? ''),
                $properties['confidence'] ?? '',
                $this->jsonCell($properties['taxonomy_hierarchy'] ?? []),
                $this->jsonCell($properties['taxonomy_alternates'] ?? []),
                (string) ($properties['address_freeform'] ?? ''),
                (string) ($address['city'] ?? ''),
                (string) ($operator['url'] ?? ''),
                (string) ($operator['phone'] ?? ''),
                (string) ($operator['email'] ?? ''),
                '',
                '',
            ], ';', '"', '');
        }

        fclose($handle);

        return [
            'path' => $path,
            'records' => $rows->count(),
        ];
    }

    public function review(string $path, bool $apply = false): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Typ-Review-CSV wurde nicht gefunden oder ist nicht lesbar: '.$path);
        }

        $sourceId = DB::table('external_sources')
            ->where('slug', OverturePlacesCsvStageService::SOURCE_SLUG)
            ->value('id');

        if (! $sourceId) {
            throw new RuntimeException('Overture-Quelle wurde nicht gefunden.');
        }

        $placeTypes = DB::table('place_types')
            ->whereIn('slug', self::ALLOWED_TYPES)
            ->where('is_active', true)
            ->pluck('id', 'slug')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach (self::ALLOWED_TYPES as $slug) {
            if (! isset($placeTypes[$slug])) {
                throw new RuntimeException('Aktiver Camperwolf-Platztyp fehlt: '.$slug);
            }
        }

        [$headers, $rows] = $this->readCsv($path);
        foreach (['external_id', 'place_id', 'current_place_type', 'target_place_type'] as $required) {
            if (! in_array($required, $headers, true)) {
                throw new RuntimeException('Pflichtspalte fehlt: '.$required);
            }
        }

        $stats = [
            'rows' => count($rows),
            'with_target' => 0,
            'would_change' => 0,
            'changed' => 0,
            'unchanged' => 0,
            'blocked' => 0,
        ];
        $changes = [];

        foreach ($rows as $row) {
            $target = trim((string) ($row['target_place_type'] ?? ''));
            if ($target === '') {
                continue;
            }

            $stats['with_target']++;

            if (! in_array($target, self::ALLOWED_TYPES, true)) {
                $stats['blocked']++;
                $changes[] = $this->resultRow($row, 'blocked', 'Unzulässiger Zieltyp: '.$target);
                continue;
            }

            $externalId = trim((string) ($row['external_id'] ?? ''));
            $placeId = filter_var($row['place_id'] ?? null, FILTER_VALIDATE_INT);

            if ($externalId === '' || $placeId === false || $placeId <= 0) {
                $stats['blocked']++;
                $changes[] = $this->resultRow($row, 'blocked', 'Ungültige external_id/place_id.');
                continue;
            }

            $record = DB::table('external_records')
                ->where('external_source_id', $sourceId)
                ->where('external_id', $externalId)
                ->where('status', 'active')
                ->where('classification', 'created')
                ->where('place_id', (int) $placeId)
                ->first(['id', 'place_id']);

            if (! $record) {
                $stats['blocked']++;
                $changes[] = $this->resultRow($row, 'blocked', 'Kein unveränderter übernommener Overture-Record gefunden.');
                continue;
            }

            $place = DB::table('places as p')
                ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
                ->where('p.id', (int) $placeId)
                ->where('p.is_active', true)
                ->first(['p.id', 'p.name', 'p.place_type_id', 'pt.slug as current_place_type']);

            if (! $place) {
                $stats['blocked']++;
                $changes[] = $this->resultRow($row, 'blocked', 'Camperwolf-Platz ist nicht aktiv oder fehlt.');
                continue;
            }

            $exportedCurrent = trim((string) ($row['current_place_type'] ?? ''));
            if ($exportedCurrent !== (string) $place->current_place_type) {
                $stats['blocked']++;
                $changes[] = $this->resultRow($row, 'blocked', 'Platztyp hat sich seit dem Export geändert.');
                continue;
            }

            if ($target === (string) $place->current_place_type) {
                $stats['unchanged']++;
                $changes[] = $this->resultRow($row, 'unchanged', 'Zieltyp entspricht dem aktuellen Typ.');
                continue;
            }

            $stats['would_change']++;
            $changes[] = $this->resultRow(
                $row,
                $apply ? 'changed' : 'would_change',
                (string) $place->current_place_type.' -> '.$target,
            );

            if (! $apply) {
                continue;
            }

            DB::transaction(function () use ($place, $target, $placeTypes, $sourceId, $record, $row): void {
                $now = now();

                DB::table('places')->where('id', $place->id)->update([
                    'place_type_id' => $placeTypes[$target],
                    'updated_at' => $now,
                ]);

                app(PlaceHistoryService::class)->addExternalSource(
                    (int) $place->id,
                    (int) $sourceId,
                    'overture_place_type_reclassified',
                    'Platztyp nach einmaliger Overture-Prüfung korrigiert.',
                    [
                        'external_record_id' => (int) $record->id,
                        'external_id' => (string) ($row['external_id'] ?? ''),
                        'old_place_type' => (string) $place->current_place_type,
                        'new_place_type' => $target,
                        'reason' => trim((string) ($row['reason'] ?? '')) ?: null,
                    ],
                );

                DB::table('audit_logs')->insert([
                    'user_id' => null,
                    'entity_type' => 'place',
                    'entity_id' => (int) $place->id,
                    'action' => 'overture_place_type_reclassified',
                    'source' => 'admin',
                    'old_values' => json_encode(['place_type' => (string) $place->current_place_type], JSON_UNESCAPED_UNICODE),
                    'new_values' => json_encode([
                        'place_type' => $target,
                        'external_record_id' => (int) $record->id,
                        'reason' => trim((string) ($row['reason'] ?? '')) ?: null,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'internal_comment' => 'Einmalige Overture-Platztypbereinigung.',
                    'created_at' => $now,
                ]);
            });

            $stats['changed']++;
        }

        return $stats + ['changes' => $changes];
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('CSV konnte nicht geöffnet werden: '.$path);
        }

        $headers = fgetcsv($handle, 0, ';', '"', '');
        if (! is_array($headers)) {
            fclose($handle);
            throw new RuntimeException('CSV enthält keine Kopfzeile.');
        }

        $headers = array_map(fn ($value) => trim((string) $value), $headers);
        $rows = [];

        while (($values = fgetcsv($handle, 0, ';', '"', '')) !== false) {
            if ($values === [null] || $values === []) {
                continue;
            }

            $values = array_pad($values, count($headers), '');
            $rows[] = array_combine($headers, array_slice($values, 0, count($headers)));
        }

        fclose($handle);

        return [$headers, $rows];
    }

    private function jsonCell(mixed $value): string
    {
        if (! is_array($value)) {
            return trim((string) $value);
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function resultRow(array $row, string $status, string $message): array
    {
        return [
            'external_id' => (string) ($row['external_id'] ?? ''),
            'place_id' => (int) ($row['place_id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'current_place_type' => (string) ($row['current_place_type'] ?? ''),
            'target_place_type' => (string) ($row['target_place_type'] ?? ''),
            'status' => $status,
            'message' => $message,
        ];
    }
}
