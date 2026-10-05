<?php

use Database\Seeders\PlaceTypeSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new PlaceTypeSeeder())->run();
    }

    public function down(): void
    {
        DB::table('place_types')
            ->whereIn('slug', [
                'campground',
                'motorhome-pitch',
                'tent-site',
                'parking',
                'rest-area',
                'free-pitch',
                'service-station',
                'camping-outdoor',
            ])
            ->update([
                'is_active' => false,
                'is_searchable' => false,
                'updated_at' => now(),
            ]);

        DB::table('place_types')
            ->whereIn('slug', ['stay', 'stay-service', 'service'])
            ->update([
                'is_searchable' => true,
                'updated_at' => now(),
            ]);
    }
};
