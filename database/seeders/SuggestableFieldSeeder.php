<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuggestableFieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            // Core place data
            ['places', 'name', false, true, false, 10],
            ['places', 'place_type_id', false, true, false, 20],
            ['places', 'latitude', false, true, false, 30],
            ['places', 'longitude', false, true, false, 40],
            ['places', 'legal_status', false, true, false, 50],
            ['places', 'opening_status', false, true, false, 60],
            // Internal workflow field used when a newly suggested place is approved.
            // It is intentionally not meant to be rendered as a normal editable place field.
            ['places', 'publication_status', false, true, false, 70],

            // Localized place texts
            ['place_translations', 'description', true, true, true, 100],
            ['place_translations', 'directions', true, true, true, 110],
            ['place_translations', 'access_information', true, true, true, 120],

            // Address
            ['place_addresses', 'country_code', true, true, true, 200],
            ['place_addresses', 'region_id', true, true, true, 210],
            ['place_addresses', 'postal_code', true, true, true, 220],
            ['place_addresses', 'city', true, true, true, 230],
            ['place_addresses', 'street', true, true, true, 240],
            ['place_addresses', 'house_number', true, true, true, 250],
            ['place_addresses', 'address_addition', true, true, true, 260],

            // Optional localized city display name
            ['place_address_city_translations', 'name', true, true, true, 300],

            // Contacts
            ['place_contacts', 'contact_type', true, true, true, 400],
            ['place_contacts', 'value', true, true, true, 410],
            ['place_contact_translations', 'label', true, true, true, 420],

            // General place details
            ['place_details', 'operator_name', true, true, true, 500],
            ['place_details', 'pitch_count', true, true, true, 510],
            ['place_details', 'minimum_stay_nights', true, true, true, 515],
            ['place_details', 'pitch_area_min_m2', true, true, true, 520],
            ['place_detail_translations', 'season_note', true, true, true, 530],

            // Vehicle suitability
            ['place_vehicle_types', 'vehicle_type_id', true, false, true, 600],
            ['place_vehicle_types', 'capacity', false, true, false, 610],

            // Features and values
            ['place_features', 'feature_id', true, false, true, 700],
            ['place_features', 'status', true, true, true, 705],
            ['place_features', 'metadata', true, true, true, 707],
            ['place_features', 'comment', true, true, true, 709],
            ['place_features', 'feature_option_id', true, true, true, 710],
            ['place_features', 'value_number', true, true, true, 720],
            ['place_features', 'value_text', true, true, true, 730],
            ['place_features', 'unit_id', true, true, true, 740],
            ['place_features', 'rate_quantity', true, true, true, 750],
            ['place_features', 'rate_unit_id', true, true, true, 760],
            ['place_feature_notes', 'note', true, true, true, 770],

            // Opening hours
            ['opening_hours', 'day_type', true, true, true, 800],
            ['opening_hours', 'weekday', true, true, true, 810],
            ['opening_hours', 'opens_at', true, true, true, 820],
            ['opening_hours', 'closes_at', true, true, true, 830],
            ['opening_hours', 'is_closed', true, true, true, 840],
            ['opening_hours', 'is_24_hours', true, true, true, 850],
            ['opening_hours', 'by_appointment_only', true, true, true, 860],
            ['opening_hours', 'valid_from', true, true, true, 870],
            ['opening_hours', 'valid_until', true, true, true, 880],
            // Virtual aggregate field used for recurring period proposals. The
            // apply service handles the contained schedule atomically.
            ['opening_hours', 'period_schedule', true, false, false, 885],
            ['opening_hour_exceptions', 'exception_date', true, true, true, 890],
            ['opening_hour_exceptions', 'opens_at', true, true, true, 900],
            ['opening_hour_exceptions', 'closes_at', true, true, true, 910],
            ['opening_hour_exceptions', 'is_closed', true, true, true, 920],
            ['opening_hour_exceptions', 'is_24_hours', true, true, true, 930],
            ['opening_hour_exceptions', 'by_appointment_only', true, true, true, 940],

            // Prices
            ['place_prices', 'price_type_id', true, false, true, 1000],
            ['place_prices', 'amount', true, true, true, 1010],
            ['place_prices', 'currency_unit_id', true, true, true, 1020],
            ['place_prices', 'rate_quantity', true, true, true, 1030],
            ['place_prices', 'rate_unit_id', true, true, true, 1040],
            ['place_prices', 'is_included', true, true, true, 1050],
            ['place_prices', 'valid_from', true, true, true, 1060],
            ['place_prices', 'valid_until', true, true, true, 1070],
            ['place_price_translations', 'label', true, true, true, 1080],
            ['place_price_translations', 'description', true, true, true, 1090],

            // Structured seasonal pricing (virtual aggregate field).
            ['place_price_offers', 'period_pricing', true, false, false, 1100],
        ];

        foreach ($fields as [$table, $field, $allowCreate, $allowUpdate, $allowDeactivate, $sortOrder]) {
            DB::table('suggestable_fields')->updateOrInsert(
                [
                    'target_table' => $table,
                    'target_field' => $field,
                ],
                [
                    'is_suggestable' => true,
                    'allow_create' => $allowCreate,
                    'allow_update' => $allowUpdate,
                    'allow_deactivate' => $allowDeactivate,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'internal_comment' => $table === 'places' && $field === 'publication_status'
                        ? 'Internal workflow field for initial place publication approval.'
                        : null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // These two field names existed in an earlier draft but never matched
        // real database columns. Keep the rows as historical reference, but make
        // sure they can no longer be used for new change requests.
        DB::table('suggestable_fields')
            ->where('target_table', 'place_address_city_translations')
            ->where('target_field', 'city_name')
            ->update([
                'is_suggestable' => false,
                'is_active' => false,
                'internal_comment' => 'Replaced by place_address_city_translations.name.',
                'updated_at' => now(),
            ]);

        DB::table('suggestable_fields')
            ->where('target_table', 'place_detail_translations')
            ->where('target_field', 'season_information')
            ->update([
                'is_suggestable' => false,
                'is_active' => false,
                'internal_comment' => 'Replaced by place_detail_translations.season_note.',
                'updated_at' => now(),
            ]);
    }
}
