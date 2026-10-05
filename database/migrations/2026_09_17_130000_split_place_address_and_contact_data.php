<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->char('country_code', 2)->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->string('house_number', 32)->nullable();
            $table->string('address_addition')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique('place_id', 'place_addresses_place_unique');
            $table->index(['country_code', 'state', 'city'], 'place_addresses_location_index');
            $table->index(['postal_code', 'city'], 'place_addresses_postal_city_index');
        });

        Schema::create('place_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->string('contact_type', 32);
            $table->string('value', 1024);
            $table->string('label')->nullable();
            $table->unsignedInteger('sort_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['place_id', 'contact_type', 'is_active'], 'place_contacts_type_index');
            $table->index(['place_id', 'is_active', 'sort_order'], 'place_contacts_sort_index');
        });

        // Preserve any address data that may already exist. The old free-form
        // address_line cannot be reliably split, so it is retained as street.
        $places = DB::table('places')
            ->select([
                'id',
                'address_line',
                'postal_code',
                'city',
                'country_code',
                'created_at',
                'updated_at',
            ])
            ->where(function ($query) {
                $query->whereNotNull('address_line')
                    ->orWhereNotNull('postal_code')
                    ->orWhereNotNull('city')
                    ->orWhereNotNull('country_code');
            })
            ->get();

        foreach ($places as $place) {
            DB::table('place_addresses')->insert([
                'place_id' => $place->id,
                'country_code' => $place->country_code,
                'state' => null,
                'postal_code' => $place->postal_code,
                'city' => $place->city,
                'street' => $place->address_line,
                'house_number' => null,
                'address_addition' => null,
                'is_active' => true,
                'internal_comment' => $place->address_line
                    ? 'Migrated from legacy places.address_line; house number was not parsed automatically.'
                    : null,
                'created_at' => $place->created_at ?? now(),
                'updated_at' => $place->updated_at ?? now(),
            ]);
        }

        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['country_code', 'city']);
            $table->dropColumn([
                'address_line',
                'postal_code',
                'city',
                'country_code',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->string('address_line')->nullable()->after('longitude');
            $table->string('postal_code', 32)->nullable()->after('address_line');
            $table->string('city')->nullable()->after('postal_code');
            $table->char('country_code', 2)->nullable()->after('city');
            $table->index(['country_code', 'city']);
        });

        $addresses = DB::table('place_addresses')
            ->where('is_active', true)
            ->get();

        foreach ($addresses as $address) {
            $addressLine = trim(implode(' ', array_filter([
                $address->street,
                $address->house_number,
            ])));

            DB::table('places')
                ->where('id', $address->place_id)
                ->update([
                    'address_line' => $addressLine !== '' ? $addressLine : null,
                    'postal_code' => $address->postal_code,
                    'city' => $address->city,
                    'country_code' => $address->country_code,
                ]);
        }

        Schema::dropIfExists('place_contacts');
        Schema::dropIfExists('place_addresses');
    }
};
