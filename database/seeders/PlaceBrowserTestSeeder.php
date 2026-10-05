<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlaceBrowserTestSeeder extends Seeder
{
    public const SLUG_PREFIX = 'demo-place-';
    public const TEST_MARKER = '[TEST:place-browser-seeder]';

    public function run(): void
    {
        $this->guardEnvironment();

        $this->call(RemovePlaceBrowserTestDataSeeder::class);

        $placeTypes = DB::table('place_types')
            ->whereIn('slug', ['stay', 'stay-service', 'service'])
            ->where('is_active', true)
            ->pluck('id', 'slug');

        if (! $placeTypes->has('stay')) {
            throw new RuntimeException('Active place type "stay" is required. Run the normal seeders first.');
        }

        $featureSlugs = [
            'electricity', 'fresh-water', 'grey-water-disposal', 'black-water-disposal',
            'toilet', 'shower', 'wifi', 'washing-machine', 'barbecue-area', 'playground',
            'dogs-allowed', 'gas-barbecue-allowed', 'awning-allowed', 'outdoor-furniture-allowed',
            'reception', 'bread-roll-service', 'bicycle-rental', 'online-self-check-in',
            'barrier', 'turning-space', 'narrow-access', 'steep-access',
        ];

        $features = DB::table('features')
            ->whereIn('slug', $featureSlugs)
            ->where('is_active', true)
            ->pluck('id', 'slug');

        $missing = collect($featureSlugs)->reject(fn (string $slug) => $features->has($slug))->values();
        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Missing active demo features: '.$missing->implode(', '));
        }

        $anchors = [
            // NRW / Rhein-Ruhr – intentionally dense.
            ['Essen', '45127', 'DE', 51.4556, 7.0116, 8, 'urban'],
            ['Duisburg', '47051', 'DE', 51.4344, 6.7623, 5, 'urban'],
            ['Düsseldorf', '40213', 'DE', 51.2254, 6.7763, 4, 'urban'],
            ['Köln', '50667', 'DE', 50.9375, 6.9603, 5, 'urban'],
            ['Dortmund', '44135', 'DE', 51.5136, 7.4653, 4, 'urban'],
            ['Münster', '48143', 'DE', 51.9607, 7.6261, 3, 'urban'],
            ['Aachen', '52062', 'DE', 50.7753, 6.0839, 3, 'nature'],
            ['Winterberg', '59955', 'DE', 51.1935, 8.5278, 3, 'nature'],

            // Nordsee / Ostsee – tourism-heavy.
            ['Cuxhaven', '27472', 'DE', 53.8610, 8.6943, 4, 'coast'],
            ['Büsum', '25761', 'DE', 54.1297, 8.8576, 4, 'coast'],
            ['Sankt Peter-Ording', '25826', 'DE', 54.3047, 8.6411, 4, 'coast'],
            ['Flensburg', '24937', 'DE', 54.7937, 9.4469, 3, 'coast'],
            ['Kiel', '24103', 'DE', 54.3233, 10.1228, 3, 'coast'],
            ['Lübeck', '23552', 'DE', 53.8655, 10.6866, 3, 'coast'],
            ['Rostock', '18055', 'DE', 54.0924, 12.0991, 4, 'coast'],
            ['Stralsund', '18439', 'DE', 54.3091, 13.0818, 3, 'coast'],
            ['Binz', '18609', 'DE', 54.3990, 13.6103, 4, 'coast'],
            ['Heringsdorf', '17424', 'DE', 53.9538, 14.1685, 3, 'coast'],

            // Other German population/tourism centres.
            ['Hamburg', '20095', 'DE', 53.5511, 9.9937, 3, 'urban'],
            ['Bremen', '28195', 'DE', 53.0793, 8.8017, 2, 'urban'],
            ['Hannover', '30159', 'DE', 52.3759, 9.7320, 2, 'urban'],
            ['Berlin', '10115', 'DE', 52.5200, 13.4050, 3, 'urban'],
            ['Leipzig', '04109', 'DE', 51.3397, 12.3731, 2, 'urban'],
            ['Dresden', '01067', 'DE', 51.0504, 13.7373, 2, 'urban'],
            ['Frankfurt am Main', '60311', 'DE', 50.1109, 8.6821, 2, 'urban'],
            ['Heidelberg', '69117', 'DE', 49.3988, 8.6724, 2, 'nature'],
            ['Freiburg im Breisgau', '79098', 'DE', 47.9990, 7.8421, 3, 'nature'],
            ['Konstanz', '78462', 'DE', 47.6779, 9.1732, 3, 'lake'],
            ['Füssen', '87629', 'DE', 47.5714, 10.7004, 3, 'nature'],
            ['Garmisch-Partenkirchen', '82467', 'DE', 47.4917, 11.0955, 3, 'nature'],
            ['München', '80331', 'DE', 48.1351, 11.5820, 2, 'urban'],
            ['Nürnberg', '90402', 'DE', 49.4521, 11.0767, 2, 'urban'],

            // Netherlands / Belgium / Luxembourg border and holiday areas.
            ['Arnhem', '6811', 'NL', 51.9851, 5.8987, 3, 'nature'],
            ['Maastricht', '6211', 'NL', 50.8514, 5.6910, 3, 'urban'],
            ['Venlo', '5911', 'NL', 51.3704, 6.1724, 3, 'urban'],
            ['Roermond', '6041', 'NL', 51.1942, 5.9877, 3, 'lake'],
            ['Renesse', '4325', 'NL', 51.7310, 3.7734, 3, 'coast'],
            ['Domburg', '4357', 'NL', 51.5635, 3.4956, 3, 'coast'],
            ['Antwerpen', '2000', 'BE', 51.2194, 4.4025, 2, 'urban'],
            ['Liège', '4000', 'BE', 50.6326, 5.5797, 2, 'urban'],
            ['Spa', '4900', 'BE', 50.4921, 5.8640, 2, 'nature'],
            ['Ostende', '8400', 'BE', 51.2300, 2.9200, 3, 'coast'],
            ['Luxembourg', 'L-1111', 'LU', 49.6116, 6.1319, 2, 'urban'],
        ];

        $namePrefixes = [
            'Camperpark', 'Reisemobilplatz', 'Camping', 'Stellplatz', 'Camper Stop',
            'Naturcamp', 'Wohnmobilhafen', 'Campingwiese', 'Reisemobilpark', 'Camperplatz',
        ];
        $nameSuffixes = ['am See', 'am Hafen', 'im Grünen', 'am Wald', 'am Fluss', 'Panorama', 'Park', 'Ufer', 'Nord', 'Süd'];
        $streets = ['Uferweg', 'Hafenstraße', 'Waldstraße', 'Parkweg', 'Seestraße', 'Am Deich', 'Am Kanal', 'Mühlenweg', 'Dorfstraße', 'Am Campingplatz'];
        $legalStatuses = ['overnight_allowed', 'camping_allowed', 'parking_only', 'unclear'];
        $openingStatuses = ['open', 'open', 'open', 'open', 'unclear'];

        $profiles = [
            'urban' => ['electricity', 'toilet', 'wifi', 'online-self-check-in', 'dogs-allowed', 'barrier', 'turning-space'],
            'coast' => ['electricity', 'fresh-water', 'toilet', 'shower', 'dogs-allowed', 'awning-allowed', 'outdoor-furniture-allowed', 'bread-roll-service'],
            'nature' => ['fresh-water', 'dogs-allowed', 'gas-barbecue-allowed', 'outdoor-furniture-allowed', 'turning-space'],
            'lake' => ['electricity', 'fresh-water', 'toilet', 'dogs-allowed', 'awning-allowed', 'bicycle-rental', 'outdoor-furniture-allowed'],
        ];

        $optionalFeatures = [
            'grey-water-disposal', 'black-water-disposal', 'shower', 'wifi', 'washing-machine',
            'barbecue-area', 'playground', 'reception', 'bicycle-rental', 'narrow-access', 'steep-access',
        ];

        $places = [];
        $number = 1;

        foreach ($anchors as [$city, $postalCode, $countryCode, $lat, $lng, $count, $profile]) {
            for ($i = 0; $i < $count && $number <= 100; $i++, $number++) {
                // Deterministic jitter: keeps demo pins close to their anchor without stacking them exactly.
                $latOffset = (((($number * 37) % 19) - 9) / 1000);
                $lngOffset = (((($number * 53) % 23) - 11) / 1000);

                $featureList = $profiles[$profile];

                foreach ($optionalFeatures as $featureIndex => $featureSlug) {
                    if ((($number + $featureIndex * 3) % 7) === 0) {
                        $featureList[] = $featureSlug;
                    }
                }

                $featureList = array_values(array_unique($featureList));
                $typeSlug = count($featureList) >= 8 ? 'stay-service' : 'stay';

                $places[] = [
                    $namePrefixes[($number - 1) % count($namePrefixes)].' '.$city.' '.$nameSuffixes[($number * 3) % count($nameSuffixes)],
                    $city,
                    $postalCode,
                    $countryCode,
                    $lat + $latOffset,
                    $lng + $lngOffset,
                    $typeSlug,
                    $featureList,
                ];
            }
        }

        if (count($places) !== 100) {
            throw new RuntimeException('Browser demo configuration must generate exactly 100 places; generated '.count($places).'.');
        }

        $now = now();

        DB::transaction(function () use ($places, $placeTypes, $features, $streets, $legalStatuses, $openingStatuses, $now): void {
            foreach ($places as $index => [$name, $city, $postalCode, $countryCode, $lat, $lng, $typeSlug, $featureList]) {
                $placeTypeId = $placeTypes[$typeSlug] ?? $placeTypes['stay'];

                $placeId = DB::table('places')->insertGetId([
                    'place_type_id' => $placeTypeId,
                    'name' => $name,
                    'slug' => self::SLUG_PREFIX.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'publication_status' => 'published',
                    'legal_status' => $legalStatuses[$index % count($legalStatuses)],
                    'opening_status' => $openingStatuses[$index % count($openingStatuses)],
                    'is_active' => true,
                    'internal_comment' => self::TEST_MARKER,
                    'created_by' => null,
                    'approved_by' => null,
                    'approved_at' => $now,
                    'created_at' => $now->copy()->subMinutes(100 - $index),
                    'updated_at' => $now,
                ]);

                DB::table('place_addresses')->insert([
                    'place_id' => $placeId,
                    'country_code' => $countryCode,
                    'region_id' => null,
                    'postal_code' => $postalCode,
                    'city' => $city,
                    'street' => $streets[$index % count($streets)],
                    'house_number' => (string) (2 + (($index * 11) % 97)),
                    'address_addition' => null,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::TEST_MARKER,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($featureList as $featureSlug) {
                    DB::table('place_features')->insert([
                        'place_id' => $placeId,
                        'feature_id' => $features[$featureSlug],
                        'feature_option_id' => null,
                        'value_number' => null,
                        'value_text' => null,
                        'unit_key' => null,
                        'unit_id' => null,
                        'rate_quantity' => null,
                        'rate_unit_id' => null,
                        'valid_from' => $now,
                        'valid_until' => null,
                        'is_active' => true,
                        'internal_comment' => self::TEST_MARKER,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        $this->command?->info('100 browser demo places created across Germany and nearby border regions. Remove them with: php artisan db:seed --class=RemovePlaceBrowserTestDataSeeder');
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('PlaceBrowserTestSeeder may only run in local or testing environments.');
        }
    }
}
