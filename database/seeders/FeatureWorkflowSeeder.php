<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->ensureCategory('costs', 75, 'Kosten & Gebühren', 'Costs & fees');

            $this->ensureFeature('access', 'motorhome-access', 5, 'Zufahrt für Wohnmobile', 'Motorhome access');
            $this->ensureFeature('access', 'entrance-width', 25, 'Einfahrtsbreite', 'Entrance width');
            $this->ensureFeature('access', 'max-vehicle-weight', 45, 'Maximales Fahrzeuggewicht', 'Maximum vehicle weight');
            $this->ensureFeature('utilities', 'floor-drain', 45, 'Bodeneinlass / Entsorgungsrinne', 'Floor drain / disposal channel');
            $this->ensureFeature('accessibility', 'accessible-toilet', 10, 'Barrierefreies WC', 'Accessible toilet');
            $this->ensureFeature('accessibility', 'accessible-shower', 20, 'Barrierefreie Dusche', 'Accessible shower');
            $this->ensureFeature('rules', 'caravans-allowed', 90, 'Wohnwagen erlaubt', 'Caravans allowed');
            $this->ensureFeature('rules', 'tents-allowed', 100, 'Zelte erlaubt', 'Tents allowed');
            $this->ensureFeature('rules', 'motorhomes-only', 110, 'Nur Wohnmobile', 'Motorhomes only');
            $this->ensureFeature('costs', 'overnight-fee', 10, 'Übernachtungsgebühr', 'Overnight fee');
            $this->ensureFeature('costs', 'person-fee', 20, 'Personengebühr', 'Person fee');
            $this->ensureFeature('costs', 'tourist-tax', 30, 'Kurtaxe', 'Tourist tax');

            $workflows = [
                'motorhome-access' => $this->workflow('custom', [
                    $this->status('unknown', 'Unbekannt', 'Unknown'),
                    $this->status('available', 'Geeignet', 'Suitable'),
                    $this->status('unavailable', 'Ungeeignet', 'Unsuitable'),
                ]),
                'entrance-height' => $this->measurement('Einfahrtshöhe', 'Entrance height', 'm'),
                'entrance-width' => $this->measurement('Einfahrtsbreite', 'Entrance width', 'm'),
                'max-vehicle-length' => $this->measurement('Maximale Fahrzeuglänge', 'Maximum vehicle length', 'm'),
                'max-vehicle-weight' => $this->measurement('Maximales Fahrzeuggewicht', 'Maximum vehicle weight', 't'),

                'electricity' => $this->workflow('availability', $this->availabilityStatuses(), [
                    $this->numberDetail('amperage', 'Absicherung / Stromstärke', 'Amperage', 'A', ['available']),
                    $this->selectDetail('connector', 'Anschlussart', 'Connector type', [
                        ['cee-blue', 'CEE blau', 'CEE blue'],
                        ['schuko', 'Schuko', 'Schuko'],
                        ['cee-red', 'CEE rot', 'CEE red'],
                        ['other', 'Sonstige', 'Other'],
                    ], ['available']),
                    $this->pricingDetail(['kwh', 'night', 'day', 'use'], ['available']),
                ]),
                'ev-charging' => $this->workflow('availability', $this->availabilityStatuses(), [
                    $this->numberDetail('power_kw', 'Leistung', 'Power', 'kW', ['available']),
                    $this->selectDetail('connector', 'Anschlussart', 'Connector type', [
                        ['type2', 'Typ 2', 'Type 2'],
                        ['ccs', 'CCS', 'CCS'],
                        ['schuko', 'Schuko', 'Schuko'],
                        ['other', 'Sonstige', 'Other'],
                    ], ['available']),
                    $this->pricingDetail(['kwh', 'hour', 'use'], ['available']),
                ]),
                'fresh-water' => $this->workflow('availability', $this->availabilityStatuses(), [
                    $this->pricingDetail(['liter', 'use'], ['available']),
                ]),
                'grey-water-disposal' => $this->workflow('availability', $this->availabilityStatuses(), [
                    $this->pricingDetail(['use'], ['available']),
                ]),
                'black-water-disposal' => $this->workflow('availability', $this->availabilityStatuses(), [
                    $this->pricingDetail(['use'], ['available']),
                ]),
                'floor-drain' => $this->workflow('availability', $this->availabilityStatuses()),

                'toilet' => $this->workflow('availability', $this->availabilityStatuses(), [
                    $this->selectDetail('access', 'Zugang', 'Access', [
                        ['unknown', 'Unbekannt', 'Unknown'],
                        ['always', 'Jederzeit zugänglich', 'Always accessible'],
                        ['restricted', 'Nur zu bestimmten Zeiten', 'Restricted hours'],
                    ], ['available']),
                    $this->pricingDetail(['use'], ['available']),
                ]),
                'shower' => $this->workflow('availability', $this->availabilityStatuses(), [
                    $this->triStateDetail('hot_water', 'Warmwasser', 'Hot water', ['available']),
                    $this->pricingDetail(['minute', 'use'], ['available']),
                ]),
                'washbasin' => $this->workflow('availability', $this->availabilityStatuses(), [
                    $this->triStateDetail('hot_water', 'Warmwasser', 'Hot water', ['available']),
                ]),
                'accessible-toilet' => $this->workflow('availability', $this->availabilityStatuses()),
                'accessible-shower' => $this->workflow('availability', $this->availabilityStatuses()),
                'wifi' => $this->workflow('availability', $this->availabilityStatuses(), [
                    $this->pricingDetail(['day', 'use'], ['available']),
                ]),

                'distance-to-city-center' => $this->measurement('Entfernung zum Ortszentrum', 'Distance to town centre', 'km'),
                'distance-to-supermarket' => $this->measurement('Entfernung zu Einkaufsmöglichkeiten', 'Distance to shopping', 'km'),
                'distance-to-public-transport' => $this->measurement('Entfernung zum ÖPNV', 'Distance to public transport', 'km'),

                'overnight-fee' => $this->costWorkflow(['night', 'day', 'stay']),
                'person-fee' => $this->costWorkflow(['person-night', 'person-day', 'stay']),
                'tourist-tax' => $this->costWorkflow(['person-night', 'person-day', 'stay']),

                'dogs-allowed' => $this->workflow('custom', [
                    $this->status('unknown', 'Unbekannt', 'Unknown'),
                    $this->status('allowed', 'Erlaubt', 'Allowed'),
                    $this->status('unavailable', 'Nicht erlaubt', 'Not allowed'),
                ], [
                    $this->pricingDetail(['night', 'stay'], ['allowed']),
                ]),
                'reservation-required' => $this->workflow('custom', [
                    $this->status('unknown', 'Unbekannt', 'Unknown'),
                    $this->status('yes', 'Ja', 'Yes'),
                    $this->status('no', 'Nein', 'No'),
                ]),
                'max-stay-duration' => $this->workflow('custom', [
                    $this->status('unknown', 'Unbekannt', 'Unknown'),
                    $this->status('known', 'Bekannt', 'Known'),
                    $this->status('unlimited', 'Unbegrenzt', 'Unlimited'),
                ], [
                    $this->numberSelectUnitDetail('duration', 'Maximale Aufenthaltsdauer', 'Maximum stay duration', [
                        ['hour', 'Stunden', 'Hours'],
                        ['day', 'Tage', 'Days'],
                    ], ['known']),
                ]),
                'caravans-allowed' => $this->yesNoWorkflow(),
                'tents-allowed' => $this->yesNoWorkflow(),
                'motorhomes-only' => $this->yesNoWorkflow(),
            ];

            $sort = 10;
            foreach ($workflows as $slug => $config) {
                $featureId = DB::table('features')->where('slug', $slug)->value('id');
                if (! $featureId) {
                    continue;
                }

                DB::table('feature_workflows')->updateOrInsert(
                    ['feature_id' => $featureId],
                    [
                        'config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'sort_order' => $sort,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
                $sort += 10;
            }
        });
    }

    private function ensureCategory(string $slug, int $sortOrder, string $de, string $en): void
    {
        DB::table('feature_categories')->updateOrInsert(
            ['slug' => $slug],
            ['sort_order' => $sortOrder, 'is_active' => true, 'is_searchable' => true, 'updated_at' => now(), 'created_at' => now()],
        );
        $id = DB::table('feature_categories')->where('slug', $slug)->value('id');
        $this->translate('feature_category', $id, $de, $en);
    }

    private function ensureFeature(string $categorySlug, string $slug, int $sortOrder, string $de, string $en): void
    {
        $categoryId = DB::table('feature_categories')->where('slug', $categorySlug)->value('id');
        if (! $categoryId) {
            return;
        }

        DB::table('features')->updateOrInsert(
            ['slug' => $slug],
            [
                'category_id' => $categoryId,
                'value_type' => 'boolean',
                'unit_type' => null,
                'sort_order' => $sortOrder,
                'is_active' => true,
                'is_searchable' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
        $id = DB::table('features')->where('slug', $slug)->value('id');
        $this->translate('feature', $id, $de, $en);
    }

    private function translate(string $entityType, int $entityId, string $de, string $en): void
    {
        foreach (['de' => $de, 'en' => $en] as $locale => $value) {
            DB::table('translations')->updateOrInsert(
                ['entity_type' => $entityType, 'entity_id' => $entityId, 'locale' => $locale, 'field' => 'name'],
                ['value' => $value, 'is_active' => true, 'internal_comment' => null, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    private function workflow(string $mode, array $statuses, array $details = []): array
    {
        return ['status_mode' => $mode, 'status_options' => $statuses, 'details' => $details, 'comment' => true];
    }

    private function availabilityStatuses(): array
    {
        return [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('available', 'Vorhanden', 'Available'),
            $this->status('unavailable', 'Nicht vorhanden', 'Not available'),
        ];
    }

    private function status(string $value, string $de, string $en): array
    {
        return ['value' => $value, 'label' => ['de' => $de, 'en' => $en]];
    }

    private function measurement(string $de, string $en, string $unit): array
    {
        return $this->workflow('custom', [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('known', 'Bekannt', 'Known'),
        ], [
            $this->numberDetail('value', $de, $en, $unit, ['known']),
        ]);
    }

    private function yesNoWorkflow(): array
    {
        return $this->workflow('custom', [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('yes', 'Ja', 'Yes'),
            $this->status('no', 'Nein', 'No'),
        ]);
    }

    private function costWorkflow(array $units): array
    {
        return $this->workflow('custom', [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('free', 'Kostenlos / inklusive', 'Free / included'),
            $this->status('paid', 'Kostenpflichtig', 'Paid'),
        ], [
            $this->moneyDetail('price', $units, ['paid']),
        ]);
    }

    private function numberDetail(string $key, string $de, string $en, string $unit, array $showFor): array
    {
        return ['key' => $key, 'type' => 'number', 'label' => ['de' => $de, 'en' => $en], 'unit' => $unit, 'show_for' => $showFor];
    }

    private function numberSelectUnitDetail(string $key, string $de, string $en, array $units, array $showFor): array
    {
        return ['key' => $key, 'type' => 'number_unit', 'label' => ['de' => $de, 'en' => $en], 'units' => $this->options($units), 'show_for' => $showFor];
    }

    private function selectDetail(string $key, string $de, string $en, array $options, array $showFor): array
    {
        return ['key' => $key, 'type' => 'select', 'label' => ['de' => $de, 'en' => $en], 'options' => $this->options($options), 'show_for' => $showFor];
    }

    private function triStateDetail(string $key, string $de, string $en, array $showFor): array
    {
        return ['key' => $key, 'type' => 'select', 'label' => ['de' => $de, 'en' => $en], 'options' => [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('yes', 'Ja', 'Yes'),
            $this->status('no', 'Nein', 'No'),
        ], 'show_for' => $showFor];
    }

    private function pricingDetail(array $units, array $showFor): array
    {
        return ['key' => 'pricing', 'type' => 'pricing', 'label' => ['de' => 'Kosten', 'en' => 'Cost'], 'units' => $this->billingOptions($units), 'show_for' => $showFor];
    }

    private function moneyDetail(string $key, array $units, array $showFor): array
    {
        return ['key' => $key, 'type' => 'money', 'label' => ['de' => 'Preis', 'en' => 'Price'], 'units' => $this->billingOptions($units), 'show_for' => $showFor];
    }

    private function options(array $rows): array
    {
        return array_map(fn (array $row) => $this->status($row[0], $row[1], $row[2]), $rows);
    }

    private function billingOptions(array $keys): array
    {
        $labels = [
            'kwh' => ['kWh', 'kWh'], 'night' => ['Nacht', 'Night'], 'day' => ['Tag', 'Day'], 'hour' => ['Stunde', 'Hour'],
            'minute' => ['Minute', 'Minute'], 'liter' => ['Liter', 'Liter'], 'use' => ['Nutzung', 'Use'], 'stay' => ['Aufenthalt', 'Stay'],
            'person-night' => ['Person / Nacht', 'Person / night'], 'person-day' => ['Person / Tag', 'Person / day'],
        ];
        return array_map(fn (string $key) => $this->status($key, $labels[$key][0] ?? $key, $labels[$key][1] ?? $key), $keys);
    }
}
