<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_hour_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->uuid('period_uuid');
            $table->boolean('is_year_round')->default(false);
            $table->unsignedTinyInteger('start_month')->nullable();
            $table->unsignedTinyInteger('start_day')->nullable();
            $table->unsignedTinyInteger('end_month')->nullable();
            $table->unsignedTinyInteger('end_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('version_valid_from')->nullable();
            $table->timestamp('version_valid_until')->nullable();
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['place_id', 'is_active', 'version_valid_until'], 'ohp_place_current_idx');
            $table->index(['place_id', 'start_month', 'start_day', 'end_month', 'end_day'], 'ohp_place_range_idx');
            $table->index('period_uuid', 'ohp_period_uuid_idx');
        });

        Schema::table('opening_hours', function (Blueprint $table) {
            $table->foreignId('period_id')
                ->nullable()
                ->after('place_id')
                ->constrained('opening_hour_periods')
                ->nullOnDelete();

            $table->index(['period_id', 'is_active', 'version_valid_until'], 'oh_period_current_idx');
        });

        $groups = DB::table('opening_hours')
            ->whereNull('period_id')
            ->whereNull('feature_id')
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->select(['place_id', 'valid_from', 'valid_until'])
            ->distinct()
            ->get();

        DB::table('suggestable_fields')->updateOrInsert(
            [
                'target_table' => 'opening_hours',
                'target_field' => 'period_schedule',
            ],
            [
                'is_suggestable' => true,
                'allow_create' => true,
                'allow_update' => false,
                'allow_deactivate' => false,
                'sort_order' => 885,
                'is_active' => true,
                'internal_comment' => 'Virtual aggregate field for recurring opening-hours period proposals.',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        foreach ($groups as $group) {
            $from = $group->valid_from ? new DateTimeImmutable((string) $group->valid_from) : null;
            $until = $group->valid_until ? new DateTimeImmutable((string) $group->valid_until) : null;
            $yearRound = ! $from && ! $until;
            $now = now();

            $periodId = DB::table('opening_hour_periods')->insertGetId([
                'place_id' => $group->place_id,
                'period_uuid' => (string) Str::uuid(),
                'is_year_round' => $yearRound,
                'start_month' => $yearRound ? null : (int) ($from?->format('n') ?? 1),
                'start_day' => $yearRound ? null : (int) ($from?->format('j') ?? 1),
                'end_month' => $yearRound ? null : (int) ($until?->format('n') ?? 12),
                'end_day' => $yearRound ? null : (int) ($until?->format('j') ?? 31),
                'is_active' => true,
                'version_valid_from' => $now,
                'version_valid_until' => null,
                'internal_comment' => 'Migrated from legacy opening_hours validity range.',
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $query = DB::table('opening_hours')
                ->where('place_id', $group->place_id)
                ->whereNull('period_id')
                ->whereNull('feature_id')
                ->where('is_active', true)
                ->whereNull('version_valid_until');

            $group->valid_from === null
                ? $query->whereNull('valid_from')
                : $query->whereDate('valid_from', $group->valid_from);

            $group->valid_until === null
                ? $query->whereNull('valid_until')
                : $query->whereDate('valid_until', $group->valid_until);

            $query->update([
                'period_id' => $periodId,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('suggestable_fields')
            ->where('target_table', 'opening_hours')
            ->where('target_field', 'period_schedule')
            ->update([
                'is_suggestable' => false,
                'is_active' => false,
                'updated_at' => now(),
            ]);

        Schema::table('opening_hours', function (Blueprint $table) {
            $table->dropIndex('oh_period_current_idx');
            $table->dropConstrainedForeignId('period_id');
        });

        Schema::dropIfExists('opening_hour_periods');
    }
};
