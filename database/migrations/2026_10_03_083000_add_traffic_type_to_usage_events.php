<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usage_events', function (Blueprint $table): void {
            $table->string('traffic_type', 20)->nullable()->after('audience');
            $table->index(['audience', 'traffic_type', 'created_at'], 'usage_audience_traffic_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('usage_events', function (Blueprint $table): void {
            $table->dropIndex('usage_audience_traffic_created_idx');
            $table->dropColumn('traffic_type');
        });
    }
};
