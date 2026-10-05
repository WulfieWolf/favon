<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureUnitSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('features')
            ->where('slug', 'max-vehicle-width')
            ->update([
                'unit_type' => 'width',
                'updated_at' => now(),
            ]);

        $numericFeatures = DB::table('features')
            ->where('value_type', 'number')
            ->whereNotNull('unit_type')
            ->get(['id', 'slug', 'unit_type']);

        foreach ($numericFeatures as $feature) {
            $allowedUnits = DB::table('unit_type_units')
                ->where('unit_type', $feature->unit_type)
                ->orderBy('sort_order')
                ->get(['unit_id', 'sort_order', 'is_default']);

            foreach ($allowedUnits as $allowedUnit) {
                DB::table('feature_allowed_units')->updateOrInsert(
                    [
                        'feature_id' => $feature->id,
                        'unit_id' => $allowedUnit->unit_id,
                        'role' => 'value',
                    ],
                    [
                        'sort_order' => $allowedUnit->sort_order,
                        'is_default' => $allowedUnit->is_default,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $this->replaceAllowedValueUnits('shower-duration', ['min']);
        $this->replaceAllowedValueUnits('hot-water-duration', ['min']);
        $this->replaceAllowedValueUnits('max-stay-duration', ['h', 'day']);

        $rateUnits = [
            'electricity-price' => ['kWh'],
            'fresh-water-price' => ['l'],
            'shower-price' => ['min'],
            'hot-water-price' => ['min', 'l'],
        ];

        foreach ($rateUnits as $featureSlug => $unitKeys) {
            $featureId = DB::table('features')
                ->where('slug', $featureSlug)
                ->value('id');

            if (! $featureId) {
                continue;
            }

            DB::table('feature_allowed_units')
                ->where('feature_id', $featureId)
                ->where('role', 'rate')
                ->delete();

            foreach ($unitKeys as $index => $unitKey) {
                $unitId = DB::table('units')
                    ->where('unit_key', $unitKey)
                    ->value('id');

                if (! $unitId) {
                    continue;
                }

                DB::table('feature_allowed_units')->insert([
                    'feature_id' => $featureId,
                    'unit_id' => $unitId,
                    'role' => 'rate',
                    'sort_order' => ($index + 1) * 10,
                    'is_default' => $index === 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function replaceAllowedValueUnits(string $featureSlug, array $unitKeys): void
    {
        $featureId = DB::table('features')
            ->where('slug', $featureSlug)
            ->value('id');

        if (! $featureId) {
            return;
        }

        DB::table('feature_allowed_units')
            ->where('feature_id', $featureId)
            ->where('role', 'value')
            ->delete();

        foreach ($unitKeys as $index => $unitKey) {
            $unitId = DB::table('units')
                ->where('unit_key', $unitKey)
                ->value('id');

            if (! $unitId) {
                continue;
            }

            DB::table('feature_allowed_units')->insert([
                'feature_id' => $featureId,
                'unit_id' => $unitId,
                'role' => 'value',
                'sort_order' => ($index + 1) * 10,
                'is_default' => $index === 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
