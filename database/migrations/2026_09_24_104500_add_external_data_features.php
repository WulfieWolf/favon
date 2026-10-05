<?php

use Database\Seeders\ExternalDataFeatureExtensionSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new ExternalDataFeatureExtensionSeeder)->run();
    }

    public function down(): void
    {
        // Catalogue additions are intentionally not removed on rollback.
        // They may already be referenced by community or imported place data.
    }
};
