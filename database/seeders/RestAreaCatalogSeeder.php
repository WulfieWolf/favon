<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestAreaCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $vehicleTypes = [
            ['car', 10, 'PKW', 'Car'],
            ['car-with-trailer', 12, 'PKW mit Anhänger', 'Car with trailer'],
            ['motorcycle', 15, 'Motorrad', 'Motorcycle'],
            ['small-van', 18, 'Kleintransporter', 'Small van'],
            ['truck', 20, 'LKW', 'Truck'],
            ['coach', 24, 'Reisebus', 'Coach'],
        ];

        foreach ($vehicleTypes as [$slug, $sortOrder, $de, $en]) {
            DB::table('vehicle_types')->updateOrInsert(
                ['slug' => $slug],
                [
                    'icon_id' => null,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'is_searchable' => true,
                    'internal_comment' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $id = DB::table('vehicle_types')->where('slug', $slug)->value('id');
            $this->translate('vehicle_type', $id, $de, $en, $now);
        }

        DB::table('feature_categories')->updateOrInsert(
            ['slug' => 'fuel-rest-area'],
            [
                'icon_id' => null,
                'sort_order' => 65,
                'is_active' => true,
                'is_searchable' => true,
                'internal_comment' => 'Fuel, charging and rest-area specific infrastructure and services.',
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $categoryId = DB::table('feature_categories')->where('slug', 'fuel-rest-area')->value('id');
        $this->translate('feature_category', $categoryId, 'Tanken & Rast', 'Fuel & rest area', $now);

        $features = [
            ['fuel-station', 10, 'Tankstelle vorhanden', 'Fuel station'],
            ['fuel-petrol-e5', 20, 'Super E5', 'Petrol E5'],
            ['fuel-petrol-e10', 30, 'Super E10', 'Petrol E10'],
            ['fuel-super-plus', 40, 'Super Plus', 'Premium petrol'],
            ['fuel-diesel', 50, 'Diesel', 'Diesel'],
            ['fuel-premium-diesel', 60, 'Premium-Diesel', 'Premium diesel'],
            ['fuel-adblue', 70, 'AdBlue', 'AdBlue'],
            ['fuel-lpg', 80, 'Autogas (LPG)', 'LPG'],
            ['fuel-cng', 90, 'Erdgas (CNG)', 'CNG'],
            ['fuel-hydrogen', 100, 'Wasserstoff', 'Hydrogen'],
            ['fuel-truck-pump', 110, 'LKW-Zapfsäule / Hochleistungspumpe', 'Truck / high-flow fuel pump'],
            ['rest-convenience-store', 200, 'Shop / Convenience Store', 'Convenience store'],
            ['rest-hotel', 210, 'Hotel / Motel', 'Hotel / motel'],
            ['rest-workshop', 220, 'Werkstatt / Pannenservice', 'Workshop / breakdown service'],
            ['rest-tire-service', 230, 'Reifenservice', 'Tyre service'],
            ['rest-car-wash', 240, 'PKW-Waschanlage', 'Car wash'],
            ['rest-truck-wash', 250, 'LKW-Waschanlage', 'Truck wash'],
            ['rest-secure-truck-parking', 260, 'Gesicherter LKW-Parkplatz', 'Secure truck parking'],
            ['rest-trucker-lounge', 270, 'Fahrer-Lounge / Aufenthaltsraum', 'Driver lounge'],
            ['rest-atm', 280, 'Geldautomat', 'ATM'],
            ['rest-vending-machines', 290, 'Verkaufsautomaten', 'Vending machines'],
            ['rest-picnic-area', 300, 'Picknickbereich', 'Picnic area'],
            ['rest-dog-area', 310, 'Hundeauslauf / Hundebereich', 'Dog exercise area'],
            ['rest-laundry', 320, 'Waschmaschine / Wäscheservice', 'Laundry'],
            ['rest-emergency-phone', 330, 'Notrufsäule / Notfalltelefon', 'Emergency phone'],
        ];

        foreach ($features as [$slug, $sortOrder, $de, $en]) {
            DB::table('features')->updateOrInsert(
                ['slug' => $slug],
                [
                    'category_id' => $categoryId,
                    'icon_id' => null,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'is_searchable' => true,
                    'internal_comment' => null,
                    'value_type' => 'boolean',
                    'unit_type' => null,
                    'approval_status' => 'approved',
                    'suggested_by' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => $now,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $featureId = DB::table('features')->where('slug', $slug)->value('id');
            $this->translate('feature', $featureId, $de, $en, $now);
            $this->ensureAvailabilityWorkflow($featureId, $sortOrder, $now);
        }

    }

    private function ensureAvailabilityWorkflow(int $featureId, int $sortOrder, $now): void
    {
        $config = [
            'status_mode' => 'availability',
            'status_options' => [
                ['value' => 'unknown', 'label' => ['de' => 'Unbekannt', 'en' => 'Unknown']],
                ['value' => 'available', 'label' => ['de' => 'Vorhanden', 'en' => 'Available']],
                ['value' => 'unavailable', 'label' => ['de' => 'Nicht vorhanden', 'en' => 'Not available']],
            ],
            'details' => [],
            'comment' => true,
        ];

        DB::table('feature_workflows')->updateOrInsert(
            ['feature_id' => $featureId],
            [
                'config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'sort_order' => 1000 + $sortOrder,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    private function translate(string $entityType, int $entityId, string $de, string $en, $now): void
    {
        foreach (['de' => $de, 'en' => $en] as $locale => $value) {
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
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
