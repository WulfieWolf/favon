<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $featureIds = DB::table('features')
            ->where('is_active', true)
            ->pluck('id', 'slug');

        if ($featureIds->isEmpty()) {
            return;
        }

        DB::table('external_records')
            ->where('status', 'active')
            ->where('classification', 'created')
            ->whereNotNull('place_id')
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($featureIds): void {
                foreach ($records as $record) {
                    $mapped = json_decode((string) $record->normalized_data, true);
                    if (! is_array($mapped)) {
                        continue;
                    }

                    $features = is_array($mapped['features'] ?? null)
                        ? $mapped['features']
                        : [];

                    if ($features === []) {
                        continue;
                    }

                    $placeId = (int) $record->place_id;
                    $currentFeatureIds = DB::table('place_features')
                        ->where('place_id', $placeId)
                        ->where('is_active', true)
                        ->whereNull('valid_until')
                        ->pluck('feature_id')
                        ->map(fn ($id) => (int) $id)
                        ->flip();

                    $now = now();

                    foreach ($features as $slug => $value) {
                        if (! is_string($slug) || ! is_array($value)) {
                            continue;
                        }

                        if (($value['conflict'] ?? false) === true) {
                            continue;
                        }

                        $status = $value['status'] ?? null;
                        if (! in_array($status, ['available', 'unavailable'], true)) {
                            continue;
                        }

                        $featureId = (int) ($featureIds[$slug] ?? 0);
                        if ($featureId <= 0 || $currentFeatureIds->has($featureId)) {
                            continue;
                        }

                        DB::table('place_features')->insert([
                            'place_id' => $placeId,
                            'feature_id' => $featureId,
                            'feature_option_id' => null,
                            'status' => $status,
                            'metadata' => null,
                            'value_number' => null,
                            'value_text' => null,
                            'unit_key' => null,
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

                        $currentFeatureIds->put($featureId, true);
                    }
                }
            });
    }

    public function down(): void
    {
        // Deliberately not removed automatically: after migration these rows
        // may have become the basis for later versioned community edits.
    }
};
