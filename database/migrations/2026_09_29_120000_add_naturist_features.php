<?php

use Database\Seeders\NaturistFeatureSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new NaturistFeatureSeeder)->run();
    }

    public function down(): void
    {
        // Catalog additions are intentionally not removed automatically.
    }
};
