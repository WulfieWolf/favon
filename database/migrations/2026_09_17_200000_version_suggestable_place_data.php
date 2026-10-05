<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not transactional. A failed first attempt may already
        // have added these columns even though Laravel did not record the
        // migration as completed, so add them only when they are still missing.
        foreach ([
            'place_translations',
            'place_addresses',
            'place_contacts',
            'place_details',
            'place_vehicle_types',
            'opening_hours',
            'opening_hour_exceptions',
            'place_prices',
        ] as $tableName) {
            if (! Schema::hasColumn($tableName, 'version_valid_from')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamp('version_valid_from')->nullable()->after('is_active');
                });
            }

            if (! Schema::hasColumn($tableName, 'version_valid_until')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamp('version_valid_until')->nullable()->after('version_valid_from');
                });
            }

            DB::table($tableName)
                ->whereNull('version_valid_from')
                ->update([
                    'version_valid_from' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]);
        }

        // In MySQL an index used by a foreign key cannot be dropped until
        // another suitable index exists. The old unique constraints currently
        // provide that index, so create dedicated FK indexes first.
        Schema::table('place_translations', function (Blueprint $table) {
            $table->index('place_id', 'pt_place_fk_idx');
        });
        Schema::table('place_addresses', function (Blueprint $table) {
            $table->index('place_id', 'pa_place_fk_idx');
        });
        Schema::table('place_details', function (Blueprint $table) {
            $table->index('place_id', 'pd_place_fk_idx');
        });
        Schema::table('place_vehicle_types', function (Blueprint $table) {
            $table->index('place_id', 'pvt_place_fk_idx');
        });

        // A place can have several historical versions. Only the application
        // enforces that one version is current (is_active = true).
        Schema::table('place_translations', function (Blueprint $table) {
            $table->dropUnique('place_translations_place_id_locale_unique');
            $table->index(['place_id', 'locale', 'is_active'], 'pt_place_locale_active_idx');
        });

        Schema::table('place_addresses', function (Blueprint $table) {
            $table->dropUnique('place_addresses_place_unique');
            $table->index(['place_id', 'is_active'], 'pa_place_active_idx');
        });

        Schema::table('place_details', function (Blueprint $table) {
            $table->dropUnique('place_details_place_unique');
            $table->index(['place_id', 'is_active'], 'pd_place_active_idx');
        });

        Schema::table('place_vehicle_types', function (Blueprint $table) {
            $table->dropUnique('place_vehicle_types_place_id_vehicle_type_id_unique');
            $table->index(['place_id', 'vehicle_type_id', 'is_active'], 'pvt_place_vehicle_active_idx');
        });

        Schema::table('place_contacts', function (Blueprint $table) {
            $table->index(['place_id', 'is_active', 'version_valid_until'], 'pc_current_version_idx');
        });

        Schema::table('opening_hours', function (Blueprint $table) {
            $table->index(['place_id', 'is_active', 'version_valid_until'], 'oh_current_version_idx');
        });

        Schema::table('opening_hour_exceptions', function (Blueprint $table) {
            $table->index(['place_id', 'is_active', 'version_valid_until'], 'ohe_current_version_idx');
        });

        Schema::table('place_prices', function (Blueprint $table) {
            $table->index(['place_id', 'price_type_id', 'is_active', 'version_valid_until'], 'pp_current_version_idx');
        });
    }

    public function down(): void
    {
        Schema::table('place_prices', function (Blueprint $table) {
            $table->dropIndex('pp_current_version_idx');
        });
        Schema::table('opening_hour_exceptions', function (Blueprint $table) {
            $table->dropIndex('ohe_current_version_idx');
        });
        Schema::table('opening_hours', function (Blueprint $table) {
            $table->dropIndex('oh_current_version_idx');
        });
        Schema::table('place_contacts', function (Blueprint $table) {
            $table->dropIndex('pc_current_version_idx');
        });
        Schema::table('place_vehicle_types', function (Blueprint $table) {
            $table->dropIndex('pvt_place_vehicle_active_idx');
            $table->unique(['place_id', 'vehicle_type_id'], 'place_vehicle_types_place_id_vehicle_type_id_unique');
            $table->dropIndex('pvt_place_fk_idx');
        });
        Schema::table('place_details', function (Blueprint $table) {
            $table->dropIndex('pd_place_active_idx');
            $table->unique('place_id', 'place_details_place_unique');
            $table->dropIndex('pd_place_fk_idx');
        });
        Schema::table('place_addresses', function (Blueprint $table) {
            $table->dropIndex('pa_place_active_idx');
            $table->unique('place_id', 'place_addresses_place_unique');
            $table->dropIndex('pa_place_fk_idx');
        });
        Schema::table('place_translations', function (Blueprint $table) {
            $table->dropIndex('pt_place_locale_active_idx');
            $table->unique(['place_id', 'locale'], 'place_translations_place_id_locale_unique');
            $table->dropIndex('pt_place_fk_idx');
        });

        foreach ([
            'place_prices',
            'opening_hour_exceptions',
            'opening_hours',
            'place_vehicle_types',
            'place_details',
            'place_contacts',
            'place_addresses',
            'place_translations',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['version_valid_from', 'version_valid_until']);
            });
        }
    }
};
