<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class OvertureCandidateExportService
{
    public function exportOpen(string $path): array
    {
        $sourceId = DB::table('external_sources')
            ->where('slug', OverturePlacesCsvStageService::SOURCE_SLUG)
            ->value('id');

        if (! $sourceId) {
            throw new RuntimeException('Overture-Quelle wurde noch nicht gestaged.');
        }

        $rows = DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active')
            ->whereNull('place_id')
            ->where(function ($query) {
                $query->whereNull('classification')
                    ->orWhere('classification', '!=', 'ignored');
            })
            ->orderBy('id')
            ->get(['id', 'external_id', 'normalized_data']);

        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException('Ausgabeordner konnte nicht erstellt werden: '.$directory);
        }

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Exportdatei konnte nicht erstellt werden: '.$path);
        }

        fputcsv($handle, [
            'record_id', 'external_id', 'name', 'source_category',
            'suggested_place_type', 'confidence', 'address', 'city',
            'postal_code', 'website', 'phone', 'email',
        ], ',', '"', '');

        $written = 0;

        foreach ($rows as $row) {
            $mapped = json_decode((string) $row->normalized_data, true);
            if (! is_array($mapped)) {
                continue;
            }

            $place = is_array($mapped['place'] ?? null) ? $mapped['place'] : [];
            $address = is_array($place['address'] ?? null) ? $place['address'] : [];
            $operator = is_array($place['operator'] ?? null) ? $place['operator'] : [];
            $props = is_array($mapped['source_properties'] ?? null) ? $mapped['source_properties'] : [];

            fputcsv($handle, [
                (int) $row->id,
                (string) $row->external_id,
                (string) ($place['name'] ?? ''),
                (string) ($props['overture_category'] ?? ''),
                (string) ($place['suggested_place_type'] ?? ''),
                $props['confidence'] ?? '',
                (string) ($props['address_freeform'] ?? ''),
                (string) ($address['city'] ?? ''),
                (string) ($address['postal_code'] ?? ''),
                (string) ($operator['url'] ?? ''),
                (string) ($operator['phone'] ?? ''),
                (string) ($operator['email'] ?? ''),
            ], ',', '"', '');

            $written++;
        }

        fclose($handle);

        return [
            'path' => $path,
            'records' => $written,
            'source_id' => (int) $sourceId,
        ];
    }
}
