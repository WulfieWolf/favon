<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureCatalogRefinementSeeder extends Seeder
{
    public function run(): void
    {
        $categorySlugs = [
            'sanitary',
            'facilities',
            'access',
            'rules',
            'surroundings',
            'services',
            'rental',
            'accessibility',
        ];

        $categoryIds = DB::table('feature_categories')
            ->whereIn('slug', $categorySlugs)
            ->pluck('id', 'slug');

        if ($categoryIds->count() !== count($categorySlugs)) {
            return;
        }

        DB::table('features')
            ->whereIn('slug', [
                'washbasin',
                'barbecue-allowed',
                'campfires-allowed',
                'self-check-in',
                'rental-camper',
            ])
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        $features = [
            // Sanitary
            ['category_slug' => 'sanitary', 'slug' => 'hairdryer', 'sort_order' => 110, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Haartrockner', 'en' => 'Hairdryer'],
            ['category_slug' => 'sanitary', 'slug' => 'dishwashing-sink', 'sort_order' => 120, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Geschirrspülbecken', 'en' => 'Dishwashing sink'],

            // Facilities
            ['category_slug' => 'facilities', 'slug' => 'dishwasher', 'sort_order' => 110, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Geschirrspüler', 'en' => 'Dishwasher'],
            ['category_slug' => 'facilities', 'slug' => 'fridge', 'sort_order' => 120, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Gemeinschaftskühlschrank', 'en' => 'Shared fridge'],
            ['category_slug' => 'facilities', 'slug' => 'freezer', 'sort_order' => 130, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Gefriermöglichkeit', 'en' => 'Freezer access'],
            ['category_slug' => 'facilities', 'slug' => 'charging-lockers', 'sort_order' => 140, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Ladeschließfächer', 'en' => 'Charging lockers'],
            ['category_slug' => 'facilities', 'slug' => 'covered-seating', 'sort_order' => 150, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Überdachter Sitzbereich', 'en' => 'Covered seating area'],

            // Access
            ['category_slug' => 'access', 'slug' => 'max-vehicle-height', 'sort_order' => 90, 'value_type' => 'number', 'unit_type' => 'length', 'de' => 'Maximale Fahrzeughöhe', 'en' => 'Maximum vehicle height'],
            ['category_slug' => 'access', 'slug' => 'max-vehicle-weight', 'sort_order' => 100, 'value_type' => 'number', 'unit_type' => 'weight', 'de' => 'Maximales Fahrzeuggewicht', 'en' => 'Maximum vehicle weight'],
            ['category_slug' => 'access', 'slug' => 'paved-access', 'sort_order' => 110, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Befestigte Zufahrt', 'en' => 'Paved access'],
            ['category_slug' => 'access', 'slug' => 'one-way-access', 'sort_order' => 120, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Einbahn-Zufahrt', 'en' => 'One-way access'],

            // Rules
            ['category_slug' => 'rules', 'slug' => 'dogs-leash-required', 'sort_order' => 90, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Leinenpflicht', 'en' => 'Dogs must be kept on a leash'],
            ['category_slug' => 'rules', 'slug' => 'quiet-hours', 'sort_order' => 100, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Ruhezeiten vorhanden', 'en' => 'Quiet hours apply'],
            ['category_slug' => 'rules', 'slug' => 'check-in-required', 'sort_order' => 110, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Anmeldung/Check-in erforderlich', 'en' => 'Check-in required'],
            ['category_slug' => 'rules', 'slug' => 'overnight-only', 'sort_order' => 120, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Nur Übernachtung, kein Campingverhalten', 'en' => 'Overnight stay only, no camping behaviour'],
            ['category_slug' => 'rules', 'slug' => 'charcoal-barbecue-allowed', 'sort_order' => 130, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Grillen mit Holzkohle erlaubt', 'en' => 'Charcoal barbecues allowed'],
            ['category_slug' => 'rules', 'slug' => 'gas-barbecue-allowed', 'sort_order' => 140, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Grillen mit Gas erlaubt', 'en' => 'Gas barbecues allowed'],
            ['category_slug' => 'rules', 'slug' => 'electric-barbecue-allowed', 'sort_order' => 150, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Elektrisches Grillen erlaubt', 'en' => 'Electric barbecues allowed'],
            ['category_slug' => 'rules', 'slug' => 'campfire-allowed', 'sort_order' => 160, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Lagerfeuer erlaubt', 'en' => 'Campfires allowed'],
            ['category_slug' => 'rules', 'slug' => 'fire-bowl-allowed', 'sort_order' => 170, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Feuerschale erlaubt', 'en' => 'Fire bowls allowed'],
            ['category_slug' => 'rules', 'slug' => 'music-allowed', 'sort_order' => 180, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Musik erlaubt', 'en' => 'Music allowed'],

            // Surroundings
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-pharmacy', 'sort_order' => 90, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zur Apotheke', 'en' => 'Distance to pharmacy'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-doctor', 'sort_order' => 100, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Arzt', 'en' => 'Distance to doctor'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-hospital', 'sort_order' => 110, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Krankenhaus', 'en' => 'Distance to hospital'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-fuel-station', 'sort_order' => 120, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zur Tankstelle', 'en' => 'Distance to fuel station'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-ev-charging', 'sort_order' => 130, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zur öffentlichen E-Ladestation', 'en' => 'Distance to public EV charging'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-laundromat', 'sort_order' => 140, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Waschsalon', 'en' => 'Distance to laundromat'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-swimming', 'sort_order' => 150, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zur Bademöglichkeit', 'en' => 'Distance to swimming area'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-dog-area', 'sort_order' => 160, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zu Hundeauslauf/Hundewiese', 'en' => 'Distance to dog exercise area'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-boat-rental', 'sort_order' => 170, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Bootsverleih', 'en' => 'Distance to boat rental'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-hardware-store', 'sort_order' => 180, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Baumarkt', 'en' => 'Distance to hardware store'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-camping-store', 'sort_order' => 190, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Camping-/Outdoor-Zubehör', 'en' => 'Distance to camping or outdoor store'],
            ['category_slug' => 'surroundings', 'slug' => 'distance-to-veterinarian', 'sort_order' => 200, 'value_type' => 'number', 'unit_type' => 'distance', 'de' => 'Entfernung zum Tierarzt', 'en' => 'Distance to veterinarian'],

            // Services
            ['category_slug' => 'services', 'slug' => 'online-self-check-in', 'sort_order' => 70, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Online-Self-Check-in', 'en' => 'Online self check-in'],
            ['category_slug' => 'services', 'slug' => 'self-check-in-terminal', 'sort_order' => 80, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Check-in-Automat vor Ort', 'en' => 'On-site self check-in terminal'],
            ['category_slug' => 'services', 'slug' => 'kiosk-on-site', 'sort_order' => 90, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Kiosk/kleiner Shop auf dem Platz', 'en' => 'On-site kiosk or small shop'],
            ['category_slug' => 'services', 'slug' => 'supermarket-on-site', 'sort_order' => 100, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Supermarkt auf dem Platz', 'en' => 'On-site supermarket'],
            ['category_slug' => 'services', 'slug' => 'restaurant-on-site', 'sort_order' => 110, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Restaurant auf dem Platz', 'en' => 'On-site restaurant'],
            ['category_slug' => 'services', 'slug' => 'postal-service', 'sort_order' => 120, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Postservice', 'en' => 'Postal service'],
            ['category_slug' => 'services', 'slug' => 'parcel-reception', 'sort_order' => 130, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Paketannahme', 'en' => 'Parcel reception'],
            ['category_slug' => 'services', 'slug' => 'internet-access', 'sort_order' => 140, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Öffentlicher Internetzugang', 'en' => 'Public internet access'],
            ['category_slug' => 'services', 'slug' => 'tourist-information', 'sort_order' => 150, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Touristeninformation', 'en' => 'Tourist information'],
            ['category_slug' => 'services', 'slug' => 'late-arrival-possible', 'sort_order' => 160, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Späte Anreise möglich', 'en' => 'Late arrival possible'],
            ['category_slug' => 'services', 'slug' => 'online-booking', 'sort_order' => 170, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Online-Buchung möglich', 'en' => 'Online booking available'],
            ['category_slug' => 'services', 'slug' => 'gas-bottle-sales', 'sort_order' => 180, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Gasflaschenverkauf', 'en' => 'Gas bottle sales'],

            // Rental
            ['category_slug' => 'rental', 'slug' => 'rental-bike', 'sort_order' => 60, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Fahrradvermietung', 'en' => 'Bike rental'],
            ['category_slug' => 'rental', 'slug' => 'rental-ebike', 'sort_order' => 70, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'E-Bike-Vermietung', 'en' => 'E-bike rental'],
            ['category_slug' => 'rental', 'slug' => 'rental-sup', 'sort_order' => 80, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'SUP-Verleih', 'en' => 'SUP rental'],
            ['category_slug' => 'rental', 'slug' => 'rental-kayak', 'sort_order' => 90, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Kajakverleih', 'en' => 'Kayak rental'],
            ['category_slug' => 'rental', 'slug' => 'rental-boat', 'sort_order' => 100, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Bootsverleih', 'en' => 'Boat rental'],
            ['category_slug' => 'rental', 'slug' => 'rental-camping-equipment', 'sort_order' => 110, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Campingausrüstung mietbar', 'en' => 'Camping equipment rental'],
            ['category_slug' => 'rental', 'slug' => 'rental-bedding', 'sort_order' => 120, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Bettwäsche mietbar', 'en' => 'Bedding rental'],
            ['category_slug' => 'rental', 'slug' => 'rental-campervan', 'sort_order' => 130, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Miet-Campervan', 'en' => 'Rental campervan'],
            ['category_slug' => 'rental', 'slug' => 'rental-motorhome', 'sort_order' => 140, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Miet-Wohnmobil', 'en' => 'Rental motorhome'],
            ['category_slug' => 'rental', 'slug' => 'rental-caravan-with-awning', 'sort_order' => 150, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Mietwohnwagen mit Vorzelt', 'en' => 'Rental caravan with awning'],
            ['category_slug' => 'rental', 'slug' => 'rental-glamping-tent', 'sort_order' => 160, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Glamping-Zelt', 'en' => 'Glamping tent rental'],

            // Accessibility
            ['category_slug' => 'accessibility', 'slug' => 'accessible-restaurant', 'sort_order' => 80, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreies Restaurant', 'en' => 'Accessible restaurant'],
            ['category_slug' => 'accessibility', 'slug' => 'accessible-shop', 'sort_order' => 90, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreier Shop/Kiosk', 'en' => 'Accessible shop or kiosk'],
            ['category_slug' => 'accessibility', 'slug' => 'accessible-playground', 'sort_order' => 100, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreier Spielplatz', 'en' => 'Accessible playground'],
            ['category_slug' => 'accessibility', 'slug' => 'accessible-pool', 'sort_order' => 110, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreier Poolzugang', 'en' => 'Accessible pool access'],
            ['category_slug' => 'accessibility', 'slug' => 'accessible-dishwashing', 'sort_order' => 120, 'value_type' => 'boolean', 'unit_type' => null, 'de' => 'Barrierefreie Spülmöglichkeit', 'en' => 'Accessible dishwashing area'],
        ];

        foreach ($features as $feature) {
            DB::table('features')->updateOrInsert(
                ['slug' => $feature['slug']],
                [
                    'category_id' => $categoryIds[$feature['category_slug']],
                    'icon_id' => null,
                    'sort_order' => $feature['sort_order'],
                    'is_active' => true,
                    'is_searchable' => true,
                    'internal_comment' => null,
                    'value_type' => $feature['value_type'],
                    'unit_type' => $feature['unit_type'],
                    'approval_status' => 'approved',
                    'suggested_by' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $featureId = DB::table('features')
                ->where('slug', $feature['slug'])
                ->value('id');

            foreach (['de', 'en'] as $locale) {
                DB::table('translations')->updateOrInsert(
                    [
                        'entity_type' => 'feature',
                        'entity_id' => $featureId,
                        'locale' => $locale,
                        'field' => 'name',
                    ],
                    [
                        'value' => $feature[$locale],
                        'is_active' => true,
                        'internal_comment' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
