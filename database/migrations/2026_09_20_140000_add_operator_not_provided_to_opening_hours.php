<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opening_hours', function (Blueprint $table) {
            $table->boolean('is_not_provided_by_operator')
                ->default(false)
                ->after('by_appointment_only');
        });

        // A short-lived earlier implementation persisted "unknown" as an empty
        // opening_hours row. Unknown now means that no information exists, so
        // retire those current empty rows while keeping their history intact.
        $now = now();

        DB::table('opening_hours')
            ->whereNull('feature_id')
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->whereNull('opens_at')
            ->whereNull('closes_at')
            ->where('is_closed', false)
            ->where('is_24_hours', false)
            ->where('by_appointment_only', false)
            ->update([
                'is_active' => false,
                'version_valid_until' => $now,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        Schema::table('opening_hours', function (Blueprint $table) {
            $table->dropColumn('is_not_provided_by_operator');
        });
    }
};
