<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $placeTypeId = DB::table('place_types')->where('slug', 'camping-outdoor')->value('id');

        if (! $placeTypeId) {
            return;
        }

        $translations = [
            'de' => [
                'name' => 'Camping-/Outdoor-Shop',
                'description' => 'Camping- oder Outdoor-Fachhandel und anderer dauerhaft campingbezogener Einzelhandel.',
            ],
            'en' => [
                'name' => 'Camping & outdoor store',
                'description' => 'Camping or outdoor retailer and other permanent camping-related retail destination.',
            ],
        ];

        foreach ($translations as $locale => $values) {
            foreach ($values as $field => $value) {
                DB::table('translations')
                    ->where('entity_type', 'place_type')
                    ->where('entity_id', $placeTypeId)
                    ->where('locale', $locale)
                    ->where('field', $field)
                    ->update([
                        'value' => $value,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        $placeTypeId = DB::table('place_types')->where('slug', 'camping-outdoor')->value('id');

        if (! $placeTypeId) {
            return;
        }

        $translations = [
            'de' => [
                'name' => 'Camping & Outdoor',
                'description' => 'Campingfachhandel, spezialisierte Werkstatt oder anderer dauerhaft campingbezogener Anlaufpunkt.',
            ],
            'en' => [
                'name' => 'Camping & outdoor',
                'description' => 'Camping retailer, specialist workshop or another permanent camping-related destination.',
            ],
        ];

        foreach ($translations as $locale => $values) {
            foreach ($values as $field => $value) {
                DB::table('translations')
                    ->where('entity_type', 'place_type')
                    ->where('entity_id', $placeTypeId)
                    ->where('locale', $locale)
                    ->where('field', $field)
                    ->update([
                        'value' => $value,
                        'updated_at' => now(),
                    ]);
            }
        }
    }
};
