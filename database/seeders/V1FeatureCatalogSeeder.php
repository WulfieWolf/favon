<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class V1FeatureCatalogSeeder extends Seeder
{
    private const RELEASE = 'v1-feature-catalog-2026-09-21';

    private const PLACE_TYPES = [
        'campground',
        'motorhome-pitch',
        'tent-site',
        'parking',
        'hiking-parking',
        'rest-area',
        'free-pitch',
        'service-station',
        'camping-outdoor',
    ];

    public function run(): void
    {
        if (DB::table('catalog_releases')->where('release_key', self::RELEASE)->exists()) {
            return;
        }

        $this->call([
            FeatureCategorySeeder::class,
            FeatureSeeder::class,
            SurroundingsFeatureSeeder::class,
            UtilitiesFeatureRefinementSeeder::class,
            FeatureCatalogRefinementSeeder::class,
            FeatureWorkflowSeeder::class,
            RestAreaCatalogSeeder::class,
        ]);

        DB::transaction(function (): void {
            $this->ensureCategory('dogs', 55, 'Mit Hund', 'Travelling with dogs');

            DB::table('feature_categories')->update(['is_system' => true]);
            DB::table('features')->update(['is_system' => true]);

            $this->moveFeature('ev-charging', 'utilities');
            $this->moveFeature('dogs-allowed', 'dogs');
            $this->moveFeature('dogs-leash-required', 'dogs');
            $this->moveFeature('distance-to-dog-area', 'dogs');
            $this->moveFeature('distance-to-veterinarian', 'dogs');

            $this->translateFeature('distance-to-dog-area', 'Entfernung zur Hundewiese/Freilauffläche', 'Distance to dog exercise area');
            $this->translateFeature('entrance-height', 'Maximale Durchfahrtshöhe', 'Maximum clearance height');

            DB::table('features')->where('slug', 'max-vehicle-weight')->update([
                'value_type' => 'number',
                'unit_type' => 'weight',
                'updated_at' => now(),
            ]);

            foreach ($this->newFeatures() as $feature) {
                $this->ensureFeature(...$feature);
            }

            $this->ensureSurfaceOptions();
            $this->deactivateLegacyFeatures();
            $this->ensureWorkflows();
            $this->ensurePlaceTypeVisibility();
            $this->ensureStructuredPriceProducts();
            $this->ensureAdminPermission();

            DB::table('catalog_releases')->insert([
                'release_key' => self::RELEASE,
                'applied_at' => now(),
            ]);
        });
    }

    private function newFeatures(): array
    {
        return [
            ['access', 'level-pitch', 130, 'boolean', null, 'Ebene Stellfläche', 'Level pitch'],
            ['access', 'drive-through-pitch', 140, 'boolean', null, 'Durchfahrtsstellplatz', 'Drive-through pitch'],
            ['facilities', 'waste-bins', 160, 'boolean', null, 'Müllentsorgung / Abfallbehälter', 'Waste disposal / bins'],
            ['facilities', 'site-lighting', 170, 'boolean', null, 'Platzbeleuchtung', 'Site lighting'],
            ['dogs', 'dog-shower', 20, 'boolean', null, 'Hundedusche', 'Dog shower'],
            ['dogs', 'dog-off-leash-fenced', 30, 'boolean', null, 'Eingezäunte Freilauffläche', 'Fenced off-leash area'],
            ['dogs', 'dog-off-leash-unfenced', 40, 'boolean', null, 'Nicht eingezäunte Freilauffläche', 'Unfenced off-leash area'],
            ['dogs', 'dog-waste-bag-dispenser', 50, 'boolean', null, 'Hundekotbeutelspender', 'Dog waste bag dispenser'],
            ['dogs', 'dog-waste-bin', 60, 'boolean', null, 'Hundekot-Abfallbehälter', 'Dog waste bin'],
            ['dogs', 'dog-swimming-area', 70, 'boolean', null, 'Hundebadeplatz am Platz', 'On-site dog swimming area'],
            ['dogs', 'dog-water-station', 80, 'boolean', null, 'Hundetränke', 'Dog water station'],
            ['dogs', 'distance-to-dog-beach', 110, 'number', 'distance', 'Entfernung zum Hundestrand', 'Distance to dog beach'],
        ];
    }

    private function deactivateLegacyFeatures(): void
    {
        $slugs = [
            'barbecue-allowed',
            'bicycle-rental',
            'campfires-allowed',
            'dryer-price',
            'electricity-amperage',
            'electricity-price',
            'fresh-water-distance',
            'fresh-water-price',
            'hot-water',
            'hot-water-duration',
            'hot-water-price',
            'internet-access',
            'max-vehicle-height',
            'music-allowed',
            'overnight-fee',
            'person-fee',
            'rental-camper',
            'rental-caravan-with-awning',
            'rest-convenience-store',
            'rest-dog-area',
            'rest-laundry',
            'rest-workshop',
            'self-check-in',
            'shower-duration',
            'shower-price',
            'tourist-tax',
            'washing-machine-price',
            'washbasin',
        ];

        DB::table('features')->whereIn('slug', $slugs)->update([
            'is_active' => false,
            'is_searchable' => false,
            'updated_at' => now(),
        ]);

        DB::table('feature_workflows')
            ->whereIn('feature_id', DB::table('features')->whereIn('slug', $slugs)->select('id'))
            ->update(['is_active' => false, 'updated_at' => now()]);

        DB::table('feature_categories')->where('slug', 'costs')->update([
            'is_active' => false,
            'is_searchable' => false,
            'updated_at' => now(),
        ]);
    }

    private function ensureWorkflows(): void
    {
        $features = DB::table('features as f')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->where('f.is_active', true)
            ->where('fc.is_active', true)
            ->get(['f.id', 'f.slug', 'f.value_type', 'f.unit_type', 'f.sort_order', 'fc.slug as category_slug']);

        foreach ($features as $feature) {
            $config = $this->specialWorkflow((string) $feature->slug)
                ?? $this->defaultWorkflow($feature);

            DB::table('feature_workflows')->updateOrInsert(
                ['feature_id' => $feature->id],
                [
                    'config' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'sort_order' => (int) $feature->sort_order,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function specialWorkflow(string $slug): ?array
    {
        return match ($slug) {
            'electricity' => $this->availability([
                $this->numberDetail('amperage', 'Absicherung / Stromstärke', 'Amperage', 'A'),
                $this->selectDetail('connector', 'Anschlussart', 'Connector type', [
                    ['cee-blue', 'CEE blau', 'CEE blue'],
                    ['schuko', 'Schuko', 'Schuko'],
                    ['cee-red', 'CEE rot', 'CEE red'],
                    ['other', 'Sonstige', 'Other'],
                ]),
            ]),
            'ev-charging' => $this->availability([
                $this->numberDetail('power_kw', 'Leistung', 'Power', 'kW'),
                $this->selectDetail('connector', 'Anschlussart', 'Connector type', [
                    ['type2', 'Typ 2', 'Type 2'],
                    ['ccs', 'CCS', 'CCS'],
                    ['schuko', 'Schuko', 'Schuko'],
                    ['other', 'Sonstige', 'Other'],
                ]),
            ]),
            'fresh-water' => $this->availability([
                $this->selectDetail('drinking_water', 'Als Trinkwasser ausgewiesen', 'Designated as drinking water', [
                    ['unknown', 'Unbekannt', 'Unknown'],
                    ['yes', 'Ja', 'Yes'],
                    ['no', 'Nein', 'No'],
                ]),
            ]),
            'toilet' => $this->availability([
                $this->selectDetail('access', 'Zugang', 'Access', [
                    ['unknown', 'Unbekannt', 'Unknown'],
                    ['always', 'Jederzeit zugänglich', 'Always accessible'],
                    ['restricted', 'Zeitlich eingeschränkt', 'Restricted hours'],
                ]),
            ]),
            'shower' => $this->availability([
                $this->selectDetail('hot_water', 'Warmwasser', 'Hot water', [
                    ['unknown', 'Unbekannt', 'Unknown'],
                    ['yes', 'Ja', 'Yes'],
                    ['no', 'Nein', 'No'],
                ]),
                $this->numberDetail('duration_minutes', 'Nutzungsdauer', 'Usage duration', 'min'),
            ]),
            'surface' => $this->surfaceWorkflow(),
            'dogs-allowed' => $this->dogsAllowedWorkflow(),
            'max-stay-duration' => $this->maxStayDurationWorkflow(),
            'entrance-height' => $this->knownMeasurement('value', 'Maximale Durchfahrtshöhe', 'Maximum clearance height', 'm'),
            'entrance-width' => $this->knownMeasurement('value', 'Einfahrtsbreite', 'Entrance width', 'm'),
            'max-vehicle-length' => $this->knownMeasurement('value', 'Maximale Fahrzeuglänge', 'Maximum vehicle length', 'm'),
            'max-vehicle-width' => $this->knownMeasurement('value', 'Maximale Fahrzeugbreite', 'Maximum vehicle width', 'm'),
            'max-vehicle-weight' => $this->knownMeasurement('value', 'Maximales Fahrzeuggewicht', 'Maximum vehicle weight', 't'),
            default => null,
        };
    }

    private function defaultWorkflow(object $feature): array
    {
        if ($feature->value_type === 'number') {
            return $this->knownMeasurement('value', 'Wert', 'Value', $this->defaultUnit((string) $feature->unit_type));
        }

        if ($feature->category_slug === 'rules' || $feature->slug === 'dogs-leash-required') {
            return $this->workflow('custom', [
                $this->status('unknown', 'Unbekannt', 'Unknown'),
                $this->status('yes', 'Ja', 'Yes'),
                $this->status('no', 'Nein', 'No'),
            ]);
        }

        return $this->availability();
    }

    private function ensurePlaceTypeVisibility(): void
    {
        $placeTypes = DB::table('place_types')->whereIn('slug', self::PLACE_TYPES)->pluck('id', 'slug');
        $features = DB::table('features as f')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->where('f.is_active', true)
            ->get(['f.id', 'fc.slug as category_slug']);

        foreach ($features as $feature) {
            $visibility = $this->categoryVisibility((string) $feature->category_slug);

            foreach (self::PLACE_TYPES as $placeTypeSlug) {
                if (! isset($placeTypes[$placeTypeSlug])) {
                    continue;
                }

                DB::table('feature_place_types')->insertOrIgnore([
                    'feature_id' => $feature->id,
                    'place_type_id' => $placeTypes[$placeTypeSlug],
                    'visibility' => $visibility[$placeTypeSlug] ?? 'hidden',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function categoryVisibility(string $category): array
    {
        $standard = match ($category) {
            'utilities' => ['campground', 'motorhome-pitch', 'tent-site', 'rest-area', 'service-station'],
            'sanitary' => ['campground', 'motorhome-pitch', 'tent-site', 'rest-area', 'service-station'],
            'facilities' => ['campground', 'tent-site', 'camping-outdoor'],
            'access' => self::PLACE_TYPES,
            'rules' => ['campground', 'motorhome-pitch', 'tent-site', 'parking', 'hiking-parking', 'free-pitch'],
            'dogs' => ['campground', 'motorhome-pitch', 'tent-site', 'parking', 'hiking-parking', 'rest-area', 'free-pitch'],
            'services' => ['campground', 'motorhome-pitch', 'rest-area', 'service-station', 'camping-outdoor'],
            'rental' => ['campground', 'camping-outdoor'],
            'accessibility' => self::PLACE_TYPES,
            'fuel-rest-area' => ['rest-area', 'service-station'],
            default => [],
        };

        $extended = match ($category) {
            'surroundings' => self::PLACE_TYPES,
            'utilities', 'sanitary', 'facilities', 'rules', 'dogs', 'services', 'accessibility' => self::PLACE_TYPES,
            'rental' => ['motorhome-pitch', 'tent-site'],
            'fuel-rest-area' => ['parking', 'hiking-parking', 'camping-outdoor'],
            default => [],
        };

        return collect(self::PLACE_TYPES)->mapWithKeys(function (string $slug) use ($standard, $extended) {
            return [$slug => in_array($slug, $standard, true) ? 'standard' : (in_array($slug, $extended, true) ? 'extended' : 'hidden')];
        })->all();
    }

    private function ensureStructuredPriceProducts(): void
    {
        foreach ([
            ['washing-machine', 160, 'Waschmaschine', 'Washing machine'],
            ['dryer', 170, 'Trockner', 'Dryer'],
        ] as [$slug, $sortOrder, $de, $en]) {
            DB::table('price_products')->updateOrInsert(
                ['slug' => $slug],
                [
                    'sort_order' => $sortOrder,
                    'supports_vehicle_length' => false,
                    'supports_age_range' => false,
                    'is_active' => true,
                    'is_searchable' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            $id = DB::table('price_products')->where('slug', $slug)->value('id');
            $this->translate('price_product', $id, 'name', $de, $en);

            DB::table('price_product_variants')->updateOrInsert(
                ['price_product_id' => $id, 'slug' => 'other'],
                ['sort_order' => 1000, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    private function ensureAdminPermission(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'features.manage_catalog'],
            [
                'name' => 'Merkmalskatalog verwalten',
                'category' => 'features',
                'description' => 'Kategorien, Merkmale, Workflows und Platztyp-Zuordnungen administrativ pflegen.',
                'sort_order' => 10,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $permissionId = DB::table('permissions')->where('slug', 'features.manage_catalog')->value('id');
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        if ($permissionId && $adminRoleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function ensureSurfaceOptions(): void
    {
        $featureId = DB::table('features')->where('slug', 'surface')->value('id');
        foreach ([
            ['concrete-paving', 15, 'Beton/Pflaster', 'Concrete/paving'],
            ['dirt', 35, 'Erde/Naturboden', 'Dirt/natural ground'],
            ['sand', 38, 'Sand', 'Sand'],
        ] as [$slug, $sortOrder, $de, $en]) {
            DB::table('feature_options')->updateOrInsert(
                ['feature_id' => $featureId, 'slug' => $slug],
                ['sort_order' => $sortOrder, 'is_active' => true, 'is_searchable' => true, 'updated_at' => now(), 'created_at' => now()],
            );
            $id = DB::table('feature_options')->where('feature_id', $featureId)->where('slug', $slug)->value('id');
            $this->translate('feature_option', $id, 'name', $de, $en);
        }
    }

    private function surfaceWorkflow(): array
    {
        $featureId = DB::table('features')->where('slug', 'surface')->value('id');
        $options = DB::table('feature_options as fo')
            ->where('fo.feature_id', $featureId)
            ->where('fo.is_active', true)
            ->orderBy('fo.sort_order')
            ->get(['fo.id', 'fo.slug'])
            ->map(function ($option) {
                $labels = DB::table('translations')->where('entity_type', 'feature_option')->where('entity_id', $option->id)->pluck('value', 'locale');

                return ['value' => $option->slug, 'label' => ['de' => $labels['de'] ?? $option->slug, 'en' => $labels['en'] ?? $option->slug]];
            })->all();

        return $this->workflow('custom', [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('known', 'Bekannt', 'Known'),
        ], [[
            'key' => 'surface',
            'type' => 'select',
            'label' => ['de' => 'Untergrund', 'en' => 'Surface'],
            'options' => $options,
            'show_for' => ['known'],
        ]]);
    }

    private function dogsAllowedWorkflow(): array
    {
        return $this->workflow('custom', [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('allowed', 'Erlaubt', 'Allowed'),
            $this->status('unavailable', 'Nicht erlaubt', 'Not allowed'),
        ], [
            $this->selectDetail('registration_required', 'Vorherige Anmeldung erforderlich', 'Advance registration required', [
                ['unknown', 'Unbekannt', 'Unknown'],
                ['yes', 'Ja', 'Yes'],
                ['no', 'Nein', 'No'],
            ], ['allowed']),
            $this->numberDetail('max_dogs', 'Maximale Anzahl Hunde', 'Maximum number of dogs', '', ['allowed']),
        ]);
    }

    private function maxStayDurationWorkflow(): array
    {
        return $this->workflow('custom', [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('known', 'Bekannt', 'Known'),
            $this->status('unlimited', 'Unbegrenzt', 'Unlimited'),
        ], [[
            'key' => 'duration',
            'type' => 'number_unit',
            'label' => ['de' => 'Maximale Aufenthaltsdauer', 'en' => 'Maximum stay duration'],
            'units' => [
                $this->status('hour', 'Stunden', 'Hours'),
                $this->status('day', 'Tage', 'Days'),
            ],
            'show_for' => ['known'],
        ]]);
    }

    private function ensureCategory(string $slug, int $sortOrder, string $de, string $en): void
    {
        DB::table('feature_categories')->updateOrInsert(
            ['slug' => $slug],
            ['sort_order' => $sortOrder, 'is_active' => true, 'is_searchable' => true, 'is_system' => true, 'updated_at' => now(), 'created_at' => now()],
        );
        $id = DB::table('feature_categories')->where('slug', $slug)->value('id');
        $this->translate('feature_category', $id, 'name', $de, $en);
    }

    private function ensureFeature(string $categorySlug, string $slug, int $sortOrder, string $valueType, ?string $unitType, string $de, string $en): void
    {
        $categoryId = DB::table('feature_categories')->where('slug', $categorySlug)->value('id');
        DB::table('features')->updateOrInsert(
            ['slug' => $slug],
            [
                'category_id' => $categoryId,
                'sort_order' => $sortOrder,
                'value_type' => $valueType,
                'unit_type' => $unitType,
                'approval_status' => 'approved',
                'reviewed_at' => now(),
                'is_active' => true,
                'is_searchable' => true,
                'is_system' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
        $id = DB::table('features')->where('slug', $slug)->value('id');
        $this->translate('feature', $id, 'name', $de, $en);
    }

    private function moveFeature(string $featureSlug, string $categorySlug): void
    {
        $categoryId = DB::table('feature_categories')->where('slug', $categorySlug)->value('id');
        DB::table('features')->where('slug', $featureSlug)->update(['category_id' => $categoryId, 'updated_at' => now()]);
    }

    private function translateFeature(string $slug, string $de, string $en): void
    {
        $id = DB::table('features')->where('slug', $slug)->value('id');
        if ($id) {
            $this->translate('feature', $id, 'name', $de, $en);
        }
    }

    private function translate(string $entityType, int $entityId, string $field, string $de, string $en): void
    {
        foreach (['de' => $de, 'en' => $en] as $locale => $value) {
            DB::table('translations')->updateOrInsert(
                ['entity_type' => $entityType, 'entity_id' => $entityId, 'locale' => $locale, 'field' => $field],
                ['value' => $value, 'is_active' => true, 'internal_comment' => null, 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    private function workflow(string $mode, array $statuses, array $details = []): array
    {
        return ['status_mode' => $mode, 'status_options' => $statuses, 'details' => $details, 'comment' => true];
    }

    private function availability(array $details = []): array
    {
        return $this->workflow('availability', [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('available', 'Vorhanden', 'Available'),
            $this->status('unavailable', 'Nicht vorhanden', 'Not available'),
        ], $details);
    }

    private function knownMeasurement(string $key, string $de, string $en, string $unit): array
    {
        return $this->workflow('measurement', [
            $this->status('unknown', 'Unbekannt', 'Unknown'),
            $this->status('known', 'Bekannt', 'Known'),
        ], [$this->numberDetail($key, $de, $en, $unit, ['known'])]);
    }

    private function status(string $value, string $de, string $en): array
    {
        return ['value' => $value, 'label' => ['de' => $de, 'en' => $en]];
    }

    private function numberDetail(string $key, string $de, string $en, string $unit, array $showFor = ['available']): array
    {
        return ['key' => $key, 'type' => 'number', 'label' => ['de' => $de, 'en' => $en], 'unit' => $unit, 'show_for' => $showFor];
    }

    private function selectDetail(string $key, string $de, string $en, array $options, array $showFor = ['available']): array
    {
        return [
            'key' => $key,
            'type' => 'select',
            'label' => ['de' => $de, 'en' => $en],
            'options' => array_map(fn (array $option) => ['value' => $option[0], 'label' => ['de' => $option[1], 'en' => $option[2]]], $options),
            'show_for' => $showFor,
        ];
    }

    private function defaultUnit(string $unitType): string
    {
        return match ($unitType) {
            'distance' => 'km',
            'length', 'width' => 'm',
            'time' => 'day',
            'current' => 'A',
            'power' => 'kW',
            'energy' => 'kWh',
            'weight' => 't',
            'volume' => 'l',
            default => '',
        };
    }
}
