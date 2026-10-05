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
            ->whereBetween('milestone', [13, 18])
            ->where('build', 1)
            ->delete();
    }
};
