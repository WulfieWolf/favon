<?php

use Database\Seeders\DevReleaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new DevReleaseSeeder())->run();
    }

    public function down(): void
    {
        DB::table('dev_releases')
            ->where('milestone', 19)
            ->where('build', 1)
            ->delete();
    }
};
