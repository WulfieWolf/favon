<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\DB;

class ExternalFeatureMaterializationService
{
    public function materializeForPlace(int $placeId, array $mapped): int
    {
        $features = is_array($mapped['features'] ?? null) ? $mapped['features'] : [];

        if ($features === []) {
            return 0;
        }

        $eligible = collect($features)
            ->filter(function ($value, $slug): bool {
                if (! is_string($slug) || ! is_array($value)) {
                    return false;
                }

                if (($value['conflict'] ?? false) === true) {
                    return false;
                }

                return in_array($value['status'] ?? null, ['available', 'unavailable', 'known'], true);
            });

        if ($eligible->isEmpty()) {
            return 0;
        }

        $featureIds = DB::table('features')
            ->whereIn('slug', $eligible->keys())
            ->where('is_active', true)
            ->pluck('id', 'slug');

        if ($featureIds->isEmpty()) {
            return 0;
        }

        $existingFeatureIds = DB::table('place_features')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('valid_until')
            ->whereIn('feature_id', $featureIds->values())
            ->pluck('feature_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $now = now();
        $inserted = 0;

        foreach ($eligible as $slug => $value) {
            $featureId = (int) ($featureIds[$slug] ?? 0);

            if ($featureId <= 0 || $existingFeatureIds->has($featureId)) {
                continue;
            }

            DB::table('place_features')->insert([
                'place_id' => $placeId,
                'feature_id' => $featureId,
                'feature_option_id' => null,
                'status' => (string) $value['status'],
                'metadata' => isset($value['value_number']) && is_numeric($value['value_number'])
                    ? json_encode([
                        'value' => (float) $value['value_number'],
                        'external_source_value' => $value['source_value'] ?? null,
                        'external_source_unit' => $value['source_unit'] ?? null,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : (isset($value['source_value']) ? json_encode([
                        'external_source_value' => $value['source_value'],
                        'external_source_unit' => $value['source_unit'] ?? null,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null),
                'value_number' => null,
                'value_text' => null,
                'unit_key' => isset($value['unit_key']) ? (string) $value['unit_key'] : null,
                'unit_id' => null,
                'rate_quantity' => null,
                'rate_unit_id' => null,
                'is_active' => true,
                'internal_comment' => 'Initial aus externer Quelle übernommen.',
                'valid_from' => $now,
                'valid_until' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $existingFeatureIds->put($featureId, true);
            $inserted++;
        }

        return $inserted;
    }
}
