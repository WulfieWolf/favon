<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RemovePlaceBrowserTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->guardEnvironment();

        DB::transaction(function (): void {
            $placeIds = DB::table('places')
                ->where('slug', 'like', PlaceBrowserTestSeeder::SLUG_PREFIX.'%')
                ->pluck('id');

            if ($placeIds->isEmpty()) {
                return;
            }

            $placeFeatureIds = DB::table('place_features')->whereIn('place_id', $placeIds)->pluck('id');
            $placeAddressIds = DB::table('place_addresses')->whereIn('place_id', $placeIds)->pluck('id');

            DB::table('audit_logs')
                ->where(function ($query) use ($placeIds, $placeFeatureIds, $placeAddressIds) {
                    $query->where(function ($q) use ($placeIds) {
                        $q->where('entity_type', 'place')->whereIn('entity_id', $placeIds);
                    });

                    if ($placeFeatureIds->isNotEmpty()) {
                        $query->orWhere(function ($q) use ($placeFeatureIds) {
                            $q->whereIn('entity_type', ['place_feature', 'place_features'])
                                ->whereIn('entity_id', $placeFeatureIds);
                        });
                    }

                    if ($placeAddressIds->isNotEmpty()) {
                        $query->orWhere(function ($q) use ($placeAddressIds) {
                            $q->whereIn('entity_type', ['place_address', 'place_addresses'])
                                ->whereIn('entity_id', $placeAddressIds);
                        });
                    }
                })
                ->delete();

            DB::table('places')->whereIn('id', $placeIds)->delete();
        });

        $this->command?->info('Browser demo places removed completely.');
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('RemovePlaceBrowserTestDataSeeder may only run in local or testing environments.');
        }
    }
}
