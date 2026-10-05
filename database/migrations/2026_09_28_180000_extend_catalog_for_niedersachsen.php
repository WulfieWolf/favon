<?php

use Database\Seeders\NiedersachsenFeatureCatalogSeeder;
use Database\Seeders\SuggestableFieldSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('place_details', function (Blueprint $table): void {
            if (! Schema::hasColumn('place_details', 'minimum_stay_nights')) {
                $table->unsignedSmallInteger('minimum_stay_nights')->nullable()->after('pitch_count_source');
            }
            if (! Schema::hasColumn('place_details', 'pitch_area_min_m2')) {
                $table->decimal('pitch_area_min_m2', 8, 2)->nullable()->after('minimum_stay_nights');
            }
        });

        Schema::table('photos', function (Blueprint $table): void {
            if (! Schema::hasColumn('photos', 'external_source_id')) {
                $table->foreignId('external_source_id')->nullable()->after('user_id')->constrained('external_sources')->nullOnDelete();
            }
            if (! Schema::hasColumn('photos', 'external_record_id')) {
                $table->foreignId('external_record_id')->nullable()->after('external_source_id')->constrained('external_records')->nullOnDelete();
            }
            if (! Schema::hasColumn('photos', 'external_media_id')) {
                $table->string('external_media_id', 64)->nullable()->after('external_record_id');
                $table->unique(['external_source_id', 'external_media_id'], 'photos_external_media_unique');
            }
            if (! Schema::hasColumn('photos', 'source_url')) {
                $table->text('source_url')->nullable()->after('external_media_id');
                $table->string('source_author')->nullable()->after('source_url');
                $table->string('source_copyright')->nullable()->after('source_author');
                $table->string('source_license_code', 100)->nullable()->after('source_copyright');
                $table->text('source_license_url')->nullable()->after('source_license_code');
                $table->string('source_provider')->nullable()->after('source_license_url');
                $table->timestamp('source_retrieved_at')->nullable()->after('source_provider');
            }
        });

        (new NiedersachsenFeatureCatalogSeeder)->run();
        (new SuggestableFieldSeeder)->run();
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table): void {
            if (Schema::hasColumn('photos', 'external_media_id')) {
                $table->dropUnique('photos_external_media_unique');
            }
            if (Schema::hasColumn('photos', 'external_record_id')) {
                $table->dropConstrainedForeignId('external_record_id');
            }
            if (Schema::hasColumn('photos', 'external_source_id')) {
                $table->dropConstrainedForeignId('external_source_id');
            }

            $columns = collect([
                'external_media_id',
                'source_url',
                'source_author',
                'source_copyright',
                'source_license_code',
                'source_license_url',
                'source_provider',
                'source_retrieved_at',
            ])->filter(fn (string $column) => Schema::hasColumn('photos', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('place_details', function (Blueprint $table): void {
            $columns = collect(['minimum_stay_nights', 'pitch_area_min_m2'])
                ->filter(fn (string $column) => Schema::hasColumn('place_details', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
