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
        Schema::create('price_products', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('supports_vehicle_length')->default(false);
            $table->boolean('supports_age_range')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_searchable')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('price_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_product_id')->constrained('price_products')->cascadeOnDelete();
            $table->string('slug', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['price_product_id', 'slug']);
            $table->index(['price_product_id', 'is_active', 'sort_order'], 'ppv_product_active_sort_idx');
        });

        Schema::create('price_billing_units', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('place_price_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->uuid('offer_uuid');
            $table->string('source_type', 20)->default('product');
            $table->foreignId('price_product_id')->nullable()->constrained('price_products')->nullOnDelete();
            $table->foreignId('feature_id')->nullable()->constrained('features')->nullOnDelete();
            $table->foreignId('price_product_variant_id')->nullable()->constrained('price_product_variants')->nullOnDelete();
            $table->string('custom_product_name', 160)->nullable();
            $table->string('custom_variant_name', 160)->nullable();
            $table->string('display_name', 160)->nullable();
            $table->decimal('min_vehicle_length_m', 6, 2)->nullable();
            $table->decimal('max_vehicle_length_m', 6, 2)->nullable();
            $table->unsignedSmallInteger('min_age')->nullable();
            $table->unsignedSmallInteger('max_age')->nullable();
            $table->unsignedBigInteger('linked_offer_id')->nullable();
            $table->boolean('is_refundable')->default(false);
            $table->text('condition_text')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('version_valid_from')->nullable();
            $table->timestamp('version_valid_until')->nullable();
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['place_id', 'is_active', 'version_valid_until'], 'ppo_place_current_idx');
            $table->index(['price_product_id', 'price_product_variant_id'], 'ppo_product_variant_idx');
            $table->index(['feature_id', 'is_active'], 'ppo_feature_active_idx');
            $table->index('linked_offer_id', 'ppo_linked_offer_idx');
        });

        Schema::create('place_price_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_price_offer_id')->constrained('place_price_offers')->cascadeOnDelete();
            $table->uuid('period_uuid');
            $table->boolean('is_year_round')->default(true);
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

            $table->index(['place_price_offer_id', 'is_active', 'version_valid_until'], 'ppp_offer_current_idx');
        });

        Schema::create('place_price_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_price_period_id')->constrained('place_price_periods')->cascadeOnDelete();
            $table->string('price_status', 30)->default('unknown');
            $table->decimal('amount', 12, 2)->nullable();
            $table->foreignId('currency_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('rate_quantity', 12, 3)->nullable();
            $table->foreignId('price_billing_unit_id')->nullable()->constrained('price_billing_units')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('version_valid_from')->nullable();
            $table->timestamp('version_valid_until')->nullable();
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['place_price_period_id', 'is_active', 'version_valid_until'], 'ppl_period_current_idx');
        });

        $this->seedCatalogs();

        DB::table('suggestable_fields')->updateOrInsert(
            [
                'target_table' => 'place_price_offers',
                'target_field' => 'period_pricing',
            ],
            [
                'is_suggestable' => true,
                'allow_create' => true,
                'allow_update' => false,
                'allow_deactivate' => false,
                'sort_order' => 1100,
                'is_active' => true,
                'internal_comment' => 'Virtual aggregate field for product/feature pricing and recurring price periods.',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('suggestable_fields')
            ->where('target_table', 'place_price_offers')
            ->where('target_field', 'period_pricing')
            ->update([
                'is_suggestable' => false,
                'is_active' => false,
                'updated_at' => now(),
            ]);

        Schema::dropIfExists('place_price_lines');
        Schema::dropIfExists('place_price_periods');
        Schema::dropIfExists('place_price_offers');
        Schema::dropIfExists('price_billing_units');
        Schema::dropIfExists('price_product_variants');
        Schema::dropIfExists('price_products');
    }

    private function seedCatalogs(): void
    {
        $products = [
            ['motorhome-pitch', 10, true, false, 'Wohnmobilplatz', 'Motorhome pitch'],
            ['campervan-pitch', 20, true, false, 'Campervan-Platz', 'Campervan pitch'],
            ['caravan-pitch', 30, true, false, 'Caravanplatz', 'Caravan pitch'],
            ['tent-pitch', 40, false, false, 'Zeltplatz', 'Tent pitch'],
            ['car-rooftent-pitch', 50, true, false, 'PKW-/Dachzeltplatz', 'Car / rooftop-tent pitch'],
            ['seasonal-pitch', 60, true, false, 'Dauerstellplatz', 'Seasonal pitch'],
            ['rental-accommodation', 70, false, false, 'Mietunterkunft', 'Rental accommodation'],
            ['adult', 80, false, false, 'Erwachsener', 'Adult'],
            ['child', 90, false, true, 'Kind', 'Child'],
            ['pet', 100, false, false, 'Hund/Haustier', 'Dog / pet'],
            ['extra-vehicle', 110, true, false, 'Zusätzliches Fahrzeug', 'Additional vehicle'],
            ['tourist-tax', 120, false, false, 'Kurtaxe', 'Tourist tax'],
            ['reservation-fee', 130, false, false, 'Reservierungsgebühr', 'Reservation fee'],
            ['booking-fee', 140, false, false, 'Buchungsgebühr', 'Booking fee'],
            ['deposit', 150, false, false, 'Kaution', 'Deposit'],
            ['other', 1000, false, false, 'Andere/s', 'Other'],
        ];

        foreach ($products as [$slug, $sortOrder, $vehicleLength, $ageRange, $de, $en]) {
            DB::table('price_products')->updateOrInsert(
                ['slug' => $slug],
                [
                    'sort_order' => $sortOrder,
                    'supports_vehicle_length' => $vehicleLength,
                    'supports_age_range' => $ageRange,
                    'is_active' => true,
                    'is_searchable' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            $id = DB::table('price_products')->where('slug', $slug)->value('id');
            $this->translation('price_product', $id, 'de', $de);
            $this->translation('price_product', $id, 'en', $en);
        }

        $variants = [
            'motorhome-pitch' => [
                ['standard', 10, 'Standard', 'Standard'],
                ['comfort', 20, 'Komfort', 'Comfort'],
                ['premium', 30, 'Premium', 'Premium'],
                ['private-bathroom', 40, 'mit eigenem Sanitärbereich', 'with private bathroom'],
                ['other', 1000, 'Andere/s', 'Other'],
            ],
            'campervan-pitch' => [
                ['standard', 10, 'Standard', 'Standard'],
                ['comfort', 20, 'Komfort', 'Comfort'],
                ['premium', 30, 'Premium', 'Premium'],
                ['other', 1000, 'Andere/s', 'Other'],
            ],
            'caravan-pitch' => [
                ['standard', 10, 'Standard', 'Standard'],
                ['comfort', 20, 'Komfort', 'Comfort'],
                ['premium', 30, 'Premium', 'Premium'],
                ['private-bathroom', 40, 'mit eigenem Sanitärbereich', 'with private bathroom'],
                ['other', 1000, 'Andere/s', 'Other'],
            ],
            'tent-pitch' => [
                ['standard', 10, 'Standard', 'Standard'],
                ['comfort', 20, 'Komfort', 'Comfort'],
                ['premium', 30, 'Premium', 'Premium'],
                ['other', 1000, 'Andere/s', 'Other'],
            ],
            'rental-accommodation' => [
                ['hut', 10, 'Hütte', 'Cabin'],
                ['mobile-home', 20, 'Mobilheim', 'Mobile home'],
                ['rental-tent', 30, 'Mietzelt', 'Rental tent'],
                ['pod', 40, 'Pod', 'Pod'],
                ['tiny-house', 50, 'Tiny House', 'Tiny house'],
                ['other', 1000, 'Andere/s', 'Other'],
            ],
            'pet' => [
                ['dog', 10, 'Hund', 'Dog'],
                ['other-pet', 20, 'Sonstiges Haustier', 'Other pet'],
                ['other', 1000, 'Andere/s', 'Other'],
            ],
            'extra-vehicle' => [
                ['car', 10, 'PKW', 'Car'],
                ['motorcycle', 20, 'Motorrad', 'Motorcycle'],
                ['trailer', 30, 'Anhänger', 'Trailer'],
                ['other', 1000, 'Andere/s', 'Other'],
            ],
        ];

        foreach ($variants as $productSlug => $items) {
            $productId = DB::table('price_products')->where('slug', $productSlug)->value('id');

            foreach ($items as [$slug, $sortOrder, $de, $en]) {
                DB::table('price_product_variants')->updateOrInsert(
                    ['price_product_id' => $productId, 'slug' => $slug],
                    [
                        'sort_order' => $sortOrder,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );

                $id = DB::table('price_product_variants')
                    ->where('price_product_id', $productId)
                    ->where('slug', $slug)
                    ->value('id');

                $this->translation('price_product_variant', $id, 'de', $de);
                $this->translation('price_product_variant', $id, 'en', $en);
            }
        }

        $billingUnits = [
            ['per-night', 10, 'pro Nacht', 'per night'],
            ['per-day', 20, 'pro Tag', 'per day'],
            ['per-hour', 30, 'pro Stunde', 'per hour'],
            ['per-person-night', 40, 'pro Person / Nacht', 'per person / night'],
            ['per-person-day', 50, 'pro Person / Tag', 'per person / day'],
            ['per-pitch-night', 60, 'pro Stellplatz / Nacht', 'per pitch / night'],
            ['per-vehicle-night', 70, 'pro Fahrzeug / Nacht', 'per vehicle / night'],
            ['per-use', 80, 'pro Nutzung', 'per use'],
            ['per-week', 90, 'pro Woche', 'per week'],
            ['per-month', 100, 'pro Monat', 'per month'],
            ['per-year', 110, 'pro Jahr', 'per year'],
            ['per-kwh', 120, 'pro kWh', 'per kWh'],
            ['per-liter', 130, 'pro Liter', 'per litre'],
            ['one-time', 140, 'einmalig', 'one-time'],
        ];

        foreach ($billingUnits as [$slug, $sortOrder, $de, $en]) {
            DB::table('price_billing_units')->updateOrInsert(
                ['slug' => $slug],
                [
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            $id = DB::table('price_billing_units')->where('slug', $slug)->value('id');
            $this->translation('price_billing_unit', $id, 'de', $de);
            $this->translation('price_billing_unit', $id, 'en', $en);
        }
    }

    private function translation(string $entityType, int $entityId, string $locale, string $value): void
    {
        DB::table('translations')->updateOrInsert(
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'locale' => $locale,
                'field' => 'name',
            ],
            [
                'value' => $value,
                'is_active' => true,
                'internal_comment' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
};
