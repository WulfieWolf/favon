<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['overnight', 5, false, false, 'Übernachtungspauschale', 'Overnight fee'],
            ['pitch', 8, true, false, 'Stellplatz', 'Pitch'],
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

        $vehicleBaseProducts = ['pitch', 'motorhome-pitch', 'campervan-pitch', 'caravan-pitch', 'car-rooftent-pitch'];
        $overnightBaseProducts = ['overnight', 'adult'];

        foreach ($products as [$slug, $sortOrder, $vehicleLength, $ageRange, $de, $en]) {
            DB::table('price_products')->updateOrInsert(
                ['slug' => $slug],
                [
                    'sort_order' => $sortOrder,
                    'supports_vehicle_length' => $vehicleLength,
                    'supports_age_range' => $ageRange,
                    'is_vehicle_base_price' => in_array($slug, $vehicleBaseProducts, true),
                    'is_overnight_base_price' => in_array($slug, $overnightBaseProducts, true),
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

        foreach (DB::table('price_products')->where('is_active', true)->get(['id']) as $product) {
            DB::table('price_product_variants')->updateOrInsert(
                ['price_product_id' => $product->id, 'slug' => 'other'],
                [
                    'sort_order' => 1000,
                    'is_active' => true,
                    'internal_comment' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            $variantId = DB::table('price_product_variants')
                ->where('price_product_id', $product->id)
                ->where('slug', 'other')
                ->value('id');

            $this->translation('price_product_variant', $variantId, 'de', 'Andere/s');
            $this->translation('price_product_variant', $variantId, 'en', 'Other');
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
                ['sort_order' => $sortOrder, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()],
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
}
