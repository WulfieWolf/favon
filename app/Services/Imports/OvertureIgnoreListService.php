<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class OvertureIgnoreListService
{
    public function run(string $path, bool $apply = false, ?int $actorId = null): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Ignore-CSV wurde nicht gefunden oder ist nicht lesbar: '.$path);
        }

        $sourceId = DB::table('external_sources')
            ->where('slug', OverturePlacesCsvStageService::SOURCE_SLUG)
            ->value('id');

        if (! $sourceId) {
            throw new RuntimeException('Overture-Quelle wurde noch nicht gestaged.');
        }

        $entries = $this->readCsv($path);

        $result = [
            'listed' => count($entries),
            'would_ignore' => 0,
            'ignored' => 0,
            'already_ignored' => 0,
            'not_found' => [],
            'blocked' => [],
        ];

        foreach ($entries as $entry) {
            $externalId = $entry['external_id'];
            $record = DB::table('external_records')
                ->where('external_source_id', $sourceId)
                ->where('external_id', $externalId)
                ->first(['id', 'external_id', 'status', 'place_id', 'classification']);

            if (! $record) {
                $result['not_found'][] = $externalId;
                continue;
            }

            if ($record->classification === 'ignored') {
                $result['already_ignored']++;
                continue;
            }

            if ($record->status !== 'active' || $record->place_id !== null) {
                $result['blocked'][] = $externalId;
                continue;
            }

            $result['would_ignore']++;

            if (! $apply) {
                continue;
            }

            DB::transaction(function () use ($record, $entry, $actorId): void {
                $now = now();

                DB::table('external_records')->where('id', $record->id)->update([
                    'classification' => 'ignored',
                    'classified_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('external_import_review_items')
                    ->where('external_record_id', $record->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'superseded',
                        'resolved_by' => $actorId,
                        'resolved_at' => $now,
                        'resolution_note' => 'Overture-Vorprüfung: '.$entry['ignore_reason'],
                        'updated_at' => $now,
                    ]);

                DB::table('audit_logs')->insert([
                    'user_id' => $actorId,
                    'entity_type' => 'external_record',
                    'entity_id' => (int) $record->id,
                    'action' => 'overture_candidate_ignored',
                    'source' => 'admin',
                    'old_values' => json_encode([
                        'classification' => $record->classification,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'new_values' => json_encode([
                        'external_id' => $record->external_id,
                        'classification' => 'ignored',
                        'reason' => $entry['ignore_reason'],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'internal_comment' => 'Versionierte Overture-Vorprüfung vor Kandidatenklassifizierung.',
                    'created_at' => $now,
                ]);
            });

            $result['ignored']++;
        }

        return $result;
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Ignore-CSV konnte nicht geöffnet werden.');
        }

        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');

            if (! is_array($headers)) {
                throw new RuntimeException('Ignore-CSV enthält keinen lesbaren Header.');
            }

            $headers = array_map(
                static fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"),
                $headers,
            );

            foreach (['external_id', 'name', 'ignore_reason'] as $required) {
                if (! in_array($required, $headers, true)) {
                    throw new RuntimeException('Ignore-CSV benötigt die Spalte '.$required.'.');
                }
            }

            $entries = [];
            $seen = [];

            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($values === [null] || $values === []) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    throw new RuntimeException('Ignore-CSV enthält eine Zeile mit falscher Spaltenanzahl.');
                }

                $row = array_combine($headers, $values);

                if ($row === false) {
                    continue;
                }

                $externalId = trim((string) ($row['external_id'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));
                $reason = trim((string) ($row['ignore_reason'] ?? ''));

                if ($externalId === '' || $reason === '') {
                    throw new RuntimeException('Ignore-CSV enthält einen Eintrag ohne external_id oder ignore_reason.');
                }

                if (isset($seen[$externalId])) {
                    throw new RuntimeException('Doppelte external_id in Ignore-CSV: '.$externalId);
                }

                $seen[$externalId] = true;
                $entries[] = [
                    'external_id' => $externalId,
                    'name' => $name,
                    'ignore_reason' => $reason,
                ];
            }

            return $entries;
        } finally {
            fclose($handle);
        }
    }
}
