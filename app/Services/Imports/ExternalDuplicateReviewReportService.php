<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExternalDuplicateReviewReportService
{
    public function report(string $sourceSlug = 'niedersachsen-hub-destination-one'): array
    {
        $source = DB::table('external_sources')->where('slug', $sourceSlug)->first();

        if (! $source) {
            throw new RuntimeException('Externe Quelle nicht gefunden: '.$sourceSlug);
        }

        $items = DB::table('external_import_review_items as ri')
            ->join('external_records as er', 'er.id', '=', 'ri.external_record_id')
            ->where('ri.external_source_id', $source->id)
            ->where('ri.status', 'pending')
            ->where('ri.type', 'possible_duplicate')
            ->orderBy('ri.id')
            ->get([
                'ri.id as review_id',
                'ri.details',
                'er.id as external_record_id',
                'er.external_id',
                'er.normalized_data',
            ]);

        $rows = [];

        foreach ($items as $item) {
            $details = json_decode((string) $item->details, true) ?: [];
            $normalized = json_decode((string) $item->normalized_data, true) ?: [];
            $externalName = (string) (
                $details['external_name']
                ?? $normalized['place']['name']
                ?? $item->external_id
            );

            $candidates = is_array($details['candidates'] ?? null)
                ? $details['candidates']
                : [];

            if ($candidates === []) {
                $rows[] = [
                    'review_id' => (int) $item->review_id,
                    'external_record_id' => (int) $item->external_record_id,
                    'external_id' => (string) $item->external_id,
                    'external_name' => $externalName,
                    'candidate_rank' => null,
                    'place_id' => null,
                    'candidate_name' => null,
                    'distance_m' => null,
                    'name_similarity' => null,
                ];

                continue;
            }

            foreach ($candidates as $index => $candidate) {
                if (! is_array($candidate)) {
                    continue;
                }

                $rows[] = [
                    'review_id' => (int) $item->review_id,
                    'external_record_id' => (int) $item->external_record_id,
                    'external_id' => (string) $item->external_id,
                    'external_name' => $externalName,
                    'candidate_rank' => $index + 1,
                    'place_id' => isset($candidate['place_id']) ? (int) $candidate['place_id'] : null,
                    'candidate_name' => $candidate['name'] ?? null,
                    'distance_m' => isset($candidate['distance_m']) ? (int) $candidate['distance_m'] : null,
                    'name_similarity' => isset($candidate['name_similarity']) ? (float) $candidate['name_similarity'] : null,
                ];
            }
        }

        return [
            'source_id' => (int) $source->id,
            'source_slug' => (string) $source->slug,
            'source_name' => (string) $source->name,
            'review_count' => $items->count(),
            'rows' => $rows,
        ];
    }
}
