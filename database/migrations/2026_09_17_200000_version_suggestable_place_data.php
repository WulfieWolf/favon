<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'place_translations',
            'place_addresses',
            'place_contacts',
            'place_details',
        ] as $tableName) {
            if (! Schema::hasColumn($tableName, 'version_valid_from')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->timestamp('version_valid_from')->nullable()->after('is_active');
                });
            }

            if (! Schema::hasColumn($tableName, 'version_valid_until')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->timestamp('version_valid_until')->nullable()->after('version_valid_from');
                });
            }

            DB::table($tableName)
                ->whereNull('version_valid_from')
                ->update(['version_valid_from' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
        }

        Schema::table('place_translations', function (Blueprint $table): void {
            $table->index('place_id', 'pt_place_fk_idx');
            $table->dropUnique('place_translations_place_id_locale_unique');
            $table->index(['place_id', 'locale', 'is_active'], 'pt_place_locale_active_idx');
        });

        Schema::table('place_addresses', function (Blueprint $table): void {
            $table->index('place_id', 'pa_place_fk_idx');
            $table->dropUnique('place_addresses_place_unique');
            $table->index(['place_id', 'is_active'], 'pa_place_active_idx');
        });

        Schema::table('place_details', function (Blueprint $table): void {
            $table->index('place_id', 'pd_place_fk_idx');
            $table->dropUnique('place_details_place_unique');
            $table->index(['place_id', 'is_active'], 'pd_place_active_idx');
        });

        Schema::table('place_contacts', function (Blueprint $table): void {
            $table->index(['place_id', 'is_active', 'version_valid_until'], 'pc_current_version_idx');
        });
    }

    public function down(): void
    {
        Schema::table('place_contacts', function (Blueprint $table): void {
            $table->dropIndex('pc_current_version_idx');
        });

        Schema::table('place_details', function (Blueprint $table): void {
            $table->dropIndex('pd_place_active_idx');
            $table->unique('place_id', 'place_details_place_unique');
            $table->dropIndex('pd_place_fk_idx');
        });

        Schema::table('place_addresses', function (Blueprint $table): void {
            $table->dropIndex('pa_place_active_idx');
            $table->unique('place_id', 'place_addresses_place_unique');
            $table->dropIndex('pa_place_fk_idx');
        });

        Schema::table('place_translations', function (Blueprint $table): void {
            $table->dropIndex('pt_place_locale_active_idx');
            $table->unique(['place_id', 'locale'], 'place_translations_place_id_locale_unique');
            $table->dropIndex('pt_place_fk_idx');
        });

        foreach (['place_details', 'place_contacts', 'place_addresses', 'place_translations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['version_valid_from', 'version_valid_until']);
            });
        }
    }
};
