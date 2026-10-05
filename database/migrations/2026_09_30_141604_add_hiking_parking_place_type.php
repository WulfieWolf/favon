<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $placeTypeId = DB::table('place_types')->where('slug', 'hiking-parking')->value('id');

        if ($placeTypeId) {
            DB::table('place_types')->where('id', $placeTypeId)->update([
                'sort_order' => 45,
                'is_active' => true,
                'is_searchable' => true,
                'updated_at' => $now,
            ]);
        } else {
            $placeTypeId = DB::table('place_types')->insertGetId([
                'slug' => 'hiking-parking',
                'icon_id' => null,
                'sort_order' => 45,
                'is_active' => true,
                'is_searchable' => true,
                'internal_comment' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ([
            'de' => [
                'name' => 'Wanderparkplatz',
                'description' => 'Parkplatz mit besonderem Bezug zu Wanderwegen oder Wandergebieten; ob Übernachten erlaubt oder geduldet ist, wird separat erfasst.',
            ],
            'en' => [
                'name' => 'Hiking parking',
                'description' => 'Parking area primarily serving hiking trails or hiking areas; whether overnight stays are allowed or tolerated is recorded separately.',
            ],
        ] as $locale => $values) {
            foreach ($values as $field => $value) {
                DB::table('translations')->updateOrInsert(
                    [
                        'entity_type' => 'place_type',
                        'entity_id' => $placeTypeId,
                        'locale' => $locale,
                        'field' => $field,
                    ],
                    [
                        'value' => $value,
                        'is_active' => true,
                        'internal_comment' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }

        $parkingId = DB::table('place_types')->where('slug', 'parking')->value('id');

        if ($parkingId && DB::getSchemaBuilder()->hasTable('feature_place_types')) {
            foreach (DB::table('feature_place_types')->where('place_type_id', $parkingId)->get() as $row) {
                DB::table('feature_place_types')->updateOrInsert(
                    [
                        'feature_id' => $row->feature_id,
                        'place_type_id' => $placeTypeId,
                    ],
                    [
                        'visibility' => $row->visibility,
                        'filter_priority' => $row->filter_priority,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        $placeTypeId = DB::table('place_types')->where('slug', 'hiking-parking')->value('id');

        if (! $placeTypeId) {
            return;
        }

        DB::table('translations')
            ->where('entity_type', 'place_type')
            ->where('entity_id', $placeTypeId)
            ->delete();

        DB::table('place_types')->where('id', $placeTypeId)->delete();
    }
};
