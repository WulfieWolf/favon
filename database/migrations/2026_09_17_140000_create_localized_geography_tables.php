<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->char('code', 2)->primary();
            $table->string('local_name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(10);
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'countries_active_sort_index');
        });

        Schema::create('country_translations', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2);
            $table->string('locale', 16);
            $table->string('name');
            $table->timestamps();

            $table->foreign('country_code', 'country_translations_country_fk')
                ->references('code')
                ->on('countries')
                ->cascadeOnDelete();
            $table->unique(['country_code', 'locale'], 'country_translations_unique');
            $table->index(['locale', 'name'], 'country_translations_locale_name_index');
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2);
            $table->string('code', 16)->unique();
            $table->string('local_name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(10);
            $table->timestamps();

            $table->foreign('country_code', 'regions_country_fk')
                ->references('code')
                ->on('countries')
                ->cascadeOnDelete();
            $table->index(['country_code', 'is_active', 'sort_order'], 'regions_country_active_sort_index');
        });

        Schema::create('region_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('name');
            $table->timestamps();

            $table->unique(['region_id', 'locale'], 'region_translations_unique');
            $table->index(['locale', 'name'], 'region_translations_locale_name_index');
        });

        Schema::table('place_addresses', function (Blueprint $table) {
            $table->dropIndex('place_addresses_location_index');
            $table->foreignId('region_id')
                ->nullable()
                ->after('country_code')
                ->constrained('regions')
                ->nullOnDelete();
            $table->index(['country_code', 'region_id', 'city'], 'place_addresses_location_index');
        });

        Schema::create('place_address_city_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_address_id')->constrained('place_addresses')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('name');
            $table->timestamps();

            $table->unique(['place_address_id', 'locale'], 'place_city_translations_unique');
            $table->index(['locale', 'name'], 'place_city_translations_locale_name_index');
        });

        // Existing free-form state values cannot be mapped safely to a region
        // without a reference catalogue. Preserve them in the internal comment
        // before removing the legacy column.
        DB::table('place_addresses')
            ->whereNotNull('state')
            ->orderBy('id')
            ->each(function ($address) {
                $legacyNote = 'Legacy region/state: '.$address->state;
                $comment = trim((string) ($address->internal_comment ?? ''));

                DB::table('place_addresses')
                    ->where('id', $address->id)
                    ->update([
                        'internal_comment' => $comment !== ''
                            ? $comment."\n".$legacyNote
                            : $legacyNote,
                    ]);
            });

        Schema::table('place_addresses', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_address_city_translations');

        Schema::table('place_addresses', function (Blueprint $table) {
            $table->string('state')->nullable()->after('country_code');
        });

        $addresses = DB::table('place_addresses')
            ->leftJoin('regions', 'regions.id', '=', 'place_addresses.region_id')
            ->select('place_addresses.id', 'regions.local_name')
            ->whereNotNull('place_addresses.region_id')
            ->get();

        foreach ($addresses as $address) {
            DB::table('place_addresses')
                ->where('id', $address->id)
                ->update(['state' => $address->local_name]);
        }

        Schema::table('place_addresses', function (Blueprint $table) {
            $table->dropIndex('place_addresses_location_index');
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
            $table->index(['country_code', 'state', 'city'], 'place_addresses_location_index');
        });

        Schema::dropIfExists('region_translations');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('country_translations');
        Schema::dropIfExists('countries');
    }
};
