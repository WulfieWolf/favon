<?php

namespace Database\Seeders;

use App\Services\PublicHandleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'test1234';

    public function run(): void
    {
        $ownerEmail = config('camperwolf.owner_email');
        $owner = $ownerEmail
            ? DB::table('users')->whereRaw('LOWER(email) = ?', [Str::lower(trim((string) $ownerEmail))])->first()
            : null;

        if (! $owner) {
            throw new \RuntimeException('DemoDataSeeder requires a configured CAMPERWOLF_OWNER_EMAIL matching an existing user.');
        }

        $users = $this->seedUsers();
        $places = $this->seedPlaces($users);
        $this->seedPlaceFeatures($users, $places);
        $this->seedOpeningHours($users, $places);
        $this->seedPrices($users, $places);
        $this->seedReviews($users, $places);
        $this->seedGamification($users, $places, (int) $owner->id);
    }

    public static function testPassword(): string
    {
        return self::PASSWORD;
    }

    private function seedUsers(): array
    {
        $profiles = [
            [
                'name' => 'Mara König',
                'email' => 'demo01@camperwolf.test',
                'handle' => 'NordlichtMara',
                'bio' => 'Unterwegs mit Kastenwagen und Hund. Mag kleine, ruhige Stellplätze und gute Ver-/Entsorgung.',
                'city' => 'Kiel', 'country' => 'DE', 'lat' => 54.3233, 'lon' => 10.1228,
                'birth_date' => '1987-04-12', 'gender' => 'female',
                'vehicle_type' => 'campervan', 'vehicle_details' => 'Ford Transit Custom',
                'created_days_ago' => 820,
            ],
            [
                'name' => 'Jonas Weber',
                'email' => 'demo02@camperwolf.test',
                'handle' => 'RoadJonas',
                'bio' => 'Wochenend-Camper aus dem Ruhrgebiet. Wichtig sind mir unkomplizierte Zufahrt und funktionierende Infrastruktur.',
                'city' => 'Dortmund', 'country' => 'DE', 'lat' => 51.5136, 'lon' => 7.4653,
                'birth_date' => '1992-09-03', 'gender' => 'male',
                'vehicle_type' => 'motorhome', 'vehicle_details' => 'Teilintegriertes Wohnmobil, 7 m',
                'created_days_ago' => 710,
            ],
            [
                'name' => 'Lea Hoffmann',
                'email' => 'demo03@camperwolf.test',
                'handle' => 'VanLea',
                'bio' => 'Reist gern spontan und bewertet besonders, ob Plätze praktisch und ehrlich beschrieben sind.',
                'city' => 'Leipzig', 'country' => 'DE', 'lat' => 51.3397, 'lon' => 12.3731,
                'birth_date' => '1995-02-21', 'gender' => 'female',
                'vehicle_type' => 'campervan', 'vehicle_details' => 'VW Crafter',
                'created_days_ago' => 590,
            ],
            [
                'name' => 'Tobias Schneider',
                'email' => 'demo04@camperwolf.test',
                'handle' => 'TobiOnTour',
                'bio' => 'Mit Wohnwagen und Familie unterwegs. Schaut bei Bewertungen besonders auf Zustand und Nutzbarkeit.',
                'city' => 'Mainz', 'country' => 'DE', 'lat' => 49.9929, 'lon' => 8.2473,
                'birth_date' => '1983-11-18', 'gender' => 'male',
                'vehicle_type' => 'caravan', 'vehicle_details' => 'Wohnwagengespann, ca. 12 m',
                'created_days_ago' => 470,
            ],
            [
                'name' => 'Nina Bauer',
                'email' => 'demo05@camperwolf.test',
                'handle' => 'CampNina',
                'bio' => 'Meist mit Dachzelt unterwegs. Einfache Plätze sind völlig okay, solange sie sauber und gut nutzbar sind.',
                'city' => 'Freiburg im Breisgau', 'country' => 'DE', 'lat' => 47.9990, 'lon' => 7.8421,
                'birth_date' => '1990-06-30', 'gender' => 'female',
                'vehicle_type' => 'rooftop_tent', 'vehicle_details' => 'SUV mit Dachzelt',
                'created_days_ago' => 390,
            ],
            [
                'name' => 'Daniel Krüger',
                'email' => 'demo06@camperwolf.test',
                'handle' => 'DieselDan',
                'bio' => 'Fährt einen großen Camper und achtet deshalb besonders auf Rangierfläche, Zufahrten und Durchfahrtshöhen.',
                'city' => 'Hamburg', 'country' => 'DE', 'lat' => 53.5511, 'lon' => 9.9937,
                'birth_date' => '1978-08-14', 'gender' => 'male',
                'vehicle_type' => 'motorhome', 'vehicle_details' => 'Alkoven, 7,5 t',
                'created_days_ago' => 310,
            ],
            [
                'name' => 'Sophie Martin',
                'email' => 'demo07@camperwolf.test',
                'handle' => 'SophieNomad',
                'bio' => 'Deutsch-französische Van-Reisende. Regelmäßig im Elsass, Schwarzwald und entlang des Rheins unterwegs.',
                'city' => 'Strasbourg', 'country' => 'FR', 'lat' => 48.5734, 'lon' => 7.7521,
                'birth_date' => '1989-01-09', 'gender' => 'female',
                'vehicle_type' => 'campervan', 'vehicle_details' => 'Renault Master',
                'created_days_ago' => 245,
            ],
            [
                'name' => 'Lukas Meier',
                'email' => 'demo08@camperwolf.test',
                'handle' => 'AlpenLukas',
                'bio' => 'Oft in Bayern, Tirol und Salzburg unterwegs. Mag naturnahe Plätze ohne unnötigen Schnickschnack.',
                'city' => 'München', 'country' => 'DE', 'lat' => 48.1351, 'lon' => 11.5820,
                'birth_date' => '1997-05-17', 'gender' => 'male',
                'vehicle_type' => 'tent', 'vehicle_details' => 'Zelt und gelegentlich Minicamper',
                'created_days_ago' => 180,
            ],
            [
                'name' => 'Kim Neumann',
                'email' => 'demo09@camperwolf.test',
                'handle' => 'KimsKilometer',
                'bio' => 'Quer durch Deutschland unterwegs. Hinterlässt lieber kurze, konkrete Hinweise als lange Reiseberichte.',
                'city' => 'Berlin', 'country' => 'DE', 'lat' => 52.5200, 'lon' => 13.4050,
                'birth_date' => '1993-12-05', 'gender' => 'nonbinary_other', 'gender_custom' => 'divers',
                'vehicle_type' => 'car', 'vehicle_details' => 'Kombi mit Schlafausbau',
                'created_days_ago' => 120,
            ],
            [
                'name' => 'Patrick de Vries',
                'email' => 'demo10@camperwolf.test',
                'handle' => 'PatsCamper',
                'bio' => 'Kommt aus den Niederlanden und fährt häufig zwischen Benelux und Deutschland.',
                'city' => 'Venlo', 'country' => 'NL', 'lat' => 51.3704, 'lon' => 6.1724,
                'birth_date' => '1985-03-26', 'gender' => 'male',
                'vehicle_type' => 'campervan', 'vehicle_details' => 'Mercedes Sprinter',
                'created_days_ago' => 75,
            ],
        ];

        $userRoleId = DB::table('roles')->where('slug', 'user')->value('id');
        $result = [];

        foreach ($profiles as $index => $profile) {
            $createdAt = now()->subDays($profile['created_days_ago'])->setTime(10 + ($index % 7), 15);
            $userId = (int) DB::table('users')->insertGetId([
                'name' => $profile['name'],
                'email' => $profile['email'],
                'locale' => $index === 6 || $index === 9 ? 'en' : 'de',
                'profile_photo_id' => null,
                'email_verified_at' => $createdAt->copy()->addMinutes(5),
                'password' => Hash::make(self::PASSWORD),
                'remember_token' => null,
                'last_seen_at' => now()->subHours($index * 3),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            DB::table('user_profiles')->insert([
                'user_id' => $userId,
                'public_handle' => $profile['handle'],
                'selected_badge_id' => null,
                'handle_finalized_at' => $createdAt->copy()->addDay(),
                'bio' => $profile['bio'],
                'hometown_city' => $profile['city'],
                'hometown_country_code' => $profile['country'],
                'hometown_latitude' => $profile['lat'],
                'hometown_longitude' => $profile['lon'],
                'hometown_source_id' => 'demo:'.$profile['city'],
                'birth_date' => $profile['birth_date'],
                'gender' => $profile['gender'],
                'gender_custom' => $profile['gender_custom'] ?? null,
                'vehicle_type' => $profile['vehicle_type'],
                'vehicle_details' => $profile['vehicle_details'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            DB::table('user_settings')->insert([
                'user_id' => $userId,
                'show_real_name' => false,
                'show_reviews_in_profile' => true,
                'show_photos_in_profile' => true,
                'show_join_date' => true,
                'show_activity_counts' => true,
                'allow_email_notifications' => true,
                'profile_photo_visibility' => 'public',
                'bio_visibility' => $index % 3 === 0 ? 'public' : 'registered',
                'hometown_visibility' => 'registered',
                'age_visibility' => $index % 4 === 0 ? 'public' : 'private',
                'gender_visibility' => $index % 5 === 0 ? 'registered' : 'private',
                'vehicle_visibility' => 'registered',
                'social_links_visibility' => 'registered',
                'show_gamification' => true,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            DB::table('user_notification_preferences')->insert([
                'user_id' => $userId,
                'moderation_decisions' => true,
                'favorite_changes' => true,
                'general_system' => true,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if ($userRoleId) {
                DB::table('user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => $userRoleId,
                    'assigned_by' => null,
                    'assigned_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            $result[] = (object) [
                'id' => $userId,
                'name' => $profile['name'],
                'handle' => $profile['handle'],
                'email' => $profile['email'],
                'created_at' => $createdAt,
            ];
        }

        return $result;
    }

    private function seedPlaces(array $users): array
    {
        $locations = [
            ['Flensburg', 'DE', 54.7937, 9.4469, 'Schleswig-Holstein'],
            ['Kiel', 'DE', 54.3233, 10.1228, 'Schleswig-Holstein'],
            ['Lübeck', 'DE', 53.8655, 10.6866, 'Schleswig-Holstein'],
            ['Hamburg', 'DE', 53.5511, 9.9937, 'Hamburg'],
            ['Bremen', 'DE', 53.0793, 8.8017, 'Bremen'],
            ['Hannover', 'DE', 52.3759, 9.7320, 'Niedersachsen'],
            ['Münster', 'DE', 51.9607, 7.6261, 'Nordrhein-Westfalen'],
            ['Essen', 'DE', 51.4556, 7.0116, 'Nordrhein-Westfalen'],
            ['Aachen', 'DE', 50.7753, 6.0839, 'Nordrhein-Westfalen'],
            ['Köln', 'DE', 50.9375, 6.9603, 'Nordrhein-Westfalen'],
            ['Koblenz', 'DE', 50.3569, 7.5890, 'Rheinland-Pfalz'],
            ['Trier', 'DE', 49.7499, 6.6371, 'Rheinland-Pfalz'],
            ['Saarbrücken', 'DE', 49.2402, 6.9969, 'Saarland'],
            ['Frankfurt', 'DE', 50.1109, 8.6821, 'Hessen'],
            ['Kassel', 'DE', 51.3127, 9.4797, 'Hessen'],
            ['Fulda', 'DE', 50.5558, 9.6808, 'Hessen'],
            ['Würzburg', 'DE', 49.7913, 9.9534, 'Bayern'],
            ['Nürnberg', 'DE', 49.4521, 11.0767, 'Bayern'],
            ['Regensburg', 'DE', 49.0134, 12.1016, 'Bayern'],
            ['Passau', 'DE', 48.5667, 13.4319, 'Bayern'],
            ['München', 'DE', 48.1351, 11.5820, 'Bayern'],
            ['Garmisch-Partenkirchen', 'DE', 47.4917, 11.0955, 'Bayern'],
            ['Ulm', 'DE', 48.4011, 9.9876, 'Baden-Württemberg'],
            ['Stuttgart', 'DE', 48.7758, 9.1829, 'Baden-Württemberg'],
            ['Karlsruhe', 'DE', 49.0069, 8.4037, 'Baden-Württemberg'],
            ['Freiburg', 'DE', 47.9990, 7.8421, 'Baden-Württemberg'],
            ['Konstanz', 'DE', 47.6779, 9.1732, 'Baden-Württemberg'],
            ['Mannheim', 'DE', 49.4875, 8.4660, 'Baden-Württemberg'],
            ['Erfurt', 'DE', 50.9848, 11.0299, 'Thüringen'],
            ['Jena', 'DE', 50.9271, 11.5892, 'Thüringen'],
            ['Leipzig', 'DE', 51.3397, 12.3731, 'Sachsen'],
            ['Dresden', 'DE', 51.0504, 13.7373, 'Sachsen'],
            ['Görlitz', 'DE', 51.1527, 14.9885, 'Sachsen'],
            ['Magdeburg', 'DE', 52.1205, 11.6276, 'Sachsen-Anhalt'],
            ['Potsdam', 'DE', 52.3906, 13.0645, 'Brandenburg'],
            ['Berlin', 'DE', 52.5200, 13.4050, 'Berlin'],
            ['Rostock', 'DE', 54.0924, 12.0991, 'Mecklenburg-Vorpommern'],
            ['Schwerin', 'DE', 53.6355, 11.4012, 'Mecklenburg-Vorpommern'],
            ['Venlo', 'NL', 51.3704, 6.1724, 'Limburg'],
            ['Enschede', 'NL', 52.2215, 6.8937, 'Overijssel'],
            ['Eupen', 'BE', 50.6306, 6.0332, 'Liège'],
            ['Luxembourg', 'LU', 49.6116, 6.1319, null],
            ['Strasbourg', 'FR', 48.5734, 7.7521, 'Grand Est'],
            ['Basel', 'CH', 47.5596, 7.5886, 'Basel-Stadt'],
            ['Salzburg', 'AT', 47.8095, 13.0550, 'Salzburg'],
            ['Innsbruck', 'AT', 47.2692, 11.4041, 'Tirol'],
            ['Plzeň', 'CZ', 49.7384, 13.3736, 'Plzeňský kraj'],
            ['Szczecin', 'PL', 53.4285, 14.5528, 'Zachodniopomorskie'],
            ['Sønderborg', 'DK', 54.9138, 9.7922, 'Syddanmark'],
            ['Arnhem', 'NL', 51.9851, 5.8987, 'Gelderland'],
        ];

        $placeTypeSlugs = [
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
        $typeIds = DB::table('place_types')->whereIn('slug', $placeTypeSlugs)->pluck('id', 'slug');
        $vehicleIds = DB::table('vehicle_types')->pluck('id', 'slug');

        if ($typeIds->count() < count($placeTypeSlugs)) {
            throw new \RuntimeException('Reference place types are missing. Run the normal database seeders first.');
        }

        $result = [];

        foreach ($locations as $index => [$city, $country, $lat, $lon, $state]) {
            $creator = $users[$index % count($users)];
            $typeSlug = $placeTypeSlugs[$index % count($placeTypeSlugs)];
            $typeName = match ($typeSlug) {
                'campground' => 'Campingplatz',
                'motorhome-pitch' => 'Wohnmobilstellplatz',
                'tent-site' => 'Zeltplatz',
                'parking' => 'Parkplatz',
                'hiking-parking' => 'Wanderparkplatz',
                'rest-area' => 'Rastplatz',
                'free-pitch' => 'Freier Stellplatz',
                'service-station' => 'Servicestation',
                'camping-outdoor' => 'Camping-/Outdoorplatz',
            };

            $name = 'Demo '.$typeName.' '.$city;
            $slug = Str::slug('demo-'.$typeName.'-'.$city.'-'.$index);
            $createdAt = now()->subMonths(10 - ($index % 8))->subDays($index % 17)->setTime(9, 0);
            $isSeasonal = in_array($typeSlug, ['campground', 'tent-site'], true) && $index % 4 === 0;

            $placeId = (int) DB::table('places')->insertGetId([
                'place_type_id' => $typeIds[$typeSlug],
                'name' => $name,
                'slug' => $slug,
                'latitude' => $lat,
                'longitude' => $lon,
                'publication_status' => 'published',
                'legal_status' => $index % 6 === 0 ? 'unclear' : 'allowed',
                'opening_status' => $isSeasonal ? 'seasonal' : 'open',
                'is_active' => true,
                'internal_comment' => 'Automatisch erzeugter Demo-Datensatz.',
                'created_by' => $creator->id,
                'approved_by' => null,
                'approved_at' => $createdAt->copy()->addDay(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $regionId = $country === 'DE' && $state
                ? DB::table('regions')
                    ->where('country_code', 'DE')
                    ->where('local_name', $state)
                    ->value('id')
                : null;

            DB::table('place_addresses')->insert([
                'place_id' => $placeId,
                'country_code' => $country,
                'region_id' => $regionId,
                'postal_code' => null,
                'city' => $city,
                'street' => null,
                'house_number' => null,
                'address_addition' => null,
                'is_active' => true,
                'version_valid_from' => $createdAt,
                'version_valid_until' => null,
                'internal_comment' => 'Demo-Adresse; Koordinaten sind die primäre Ortsangabe.',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            DB::table('place_translations')->insert([
                'place_id' => $placeId,
                'locale' => 'de',
                'description' => match ($typeSlug) {
                    'campground' => 'Fiktiver Camperwolf-Demo-Campingplatz mit typischen Serviceangeboten in der Region '.$city.'.',
                    'motorhome-pitch' => 'Fiktiver Wohnmobilstellplatz in der Region '.$city.' mit gemischter Ausstattung für UI- und Filterszenarien.',
                    'tent-site' => 'Fiktiver Zeltplatz in der Region '.$city.' mit einfacher bis mittlerer Ausstattung.',
                    'parking' => 'Fiktiver Parkplatz in der Region '.$city.', bei dem Übernachtungs- und Ausstattungsinformationen getestet werden.',
                    'hiking-parking' => 'Fiktiver Wanderparkplatz in der Region '.$city.' für Wander- und Parkplatzszenarien; der Übernachtungsstatus wird separat behandelt.',
                    'rest-area' => 'Fiktiver Rastplatz beziehungsweise Autohof in der Region '.$city.' für Reise- und Versorgungsszenarien.',
                    'free-pitch' => 'Fiktiver einfacher Stellplatz in der Region '.$city.' außerhalb klassischer Campingplatzstrukturen.',
                    'service-station' => 'Fiktive Camperwolf-Demo-Servicestation in der Region '.$city.' für Ver- und Entsorgungsinformationen.',
                    'camping-outdoor' => 'Fiktiver Camping- und Outdoor-Anlaufpunkt in der Region '.$city.' für Handel-, Werkstatt- und Serviceszenarien.',
                },
                'directions' => $index % 4 === 0 ? 'Demo-Hinweis: Zufahrt auch für größere Fahrzeuge vorgesehen.' : null,
                'access_information' => $index % 5 === 0 ? 'Demo-Hinweis: Die Zufahrt ist rund um die Uhr möglich.' : null,
                'is_active' => true,
                'version_valid_from' => $createdAt,
                'version_valid_until' => null,
                'internal_comment' => 'Demo-Daten.',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            DB::table('place_details')->insert([
                'place_id' => $placeId,
                'operator_name' => match ($typeSlug) {
                    'campground' => 'Demo Camping '.$city,
                    'camping-outdoor' => 'Demo Outdoor '.$city,
                    'service-station' => 'Demo Camper Service '.$city,
                    default => null,
                },
                'pitch_count' => in_array($typeSlug, ['service-station', 'camping-outdoor'], true)
                    ? null
                    : 8 + (($index * 7) % 85),
                'is_active' => true,
                'version_valid_from' => $createdAt,
                'version_valid_until' => null,
                'internal_comment' => 'Demo-Daten.',
                'created_by' => $creator->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $suitable = match ($typeSlug) {
                'tent-site' => ['tent', 'car', 'rooftop-tent'],
                'parking', 'hiking-parking' => ['car', 'campervan', 'motorhome'],
                'rest-area' => ['car', 'campervan', 'large-camper', 'motorhome', 'caravan'],
                'service-station' => ['campervan', 'large-camper', 'motorhome', 'caravan'],
                'camping-outdoor' => ['tent', 'car', 'rooftop-tent', 'campervan'],
                default => ['campervan', 'large-camper', 'motorhome', 'caravan', 'tent'],
            };

            foreach ($suitable as $vehicleSlug) {
                if (! isset($vehicleIds[$vehicleSlug])) {
                    continue;
                }

                DB::table('place_vehicle_types')->insert([
                    'place_id' => $placeId,
                    'vehicle_type_id' => $vehicleIds[$vehicleSlug],
                    'is_active' => true,
                    'version_valid_from' => $createdAt,
                    'version_valid_until' => null,
                    'internal_comment' => 'Demo-Daten.',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            $result[] = (object) [
                'id' => $placeId,
                'name' => $name,
                'slug' => $slug,
                'country' => $country,
                'type_slug' => $typeSlug,
                'place_type_id' => (int) $typeIds[$typeSlug],
                'creator_id' => (int) $creator->id,
                'created_at' => $createdAt,
                'index' => $index,
            ];
        }

        return $result;
    }

    private function seedPlaceFeatures(array $users, array $places): void
    {
        $workflows = DB::table('feature_workflows as fw')
            ->join('features as f', 'f.id', '=', 'fw.feature_id')
            ->join('feature_place_types as fpt', 'fpt.feature_id', '=', 'f.id')
            ->where('fw.is_active', true)
            ->where('f.is_active', true)
            ->whereIn('fpt.visibility', ['standard', 'extended'])
            ->orderBy('fw.sort_order')
            ->orderBy('f.sort_order')
            ->get([
                'fw.feature_id',
                'fw.config',
                'f.slug',
                'fpt.place_type_id',
            ]);

        if ($workflows->isEmpty()) {
            throw new \RuntimeException('Feature workflows are missing. Run the normal database seeders first.');
        }

        foreach ($places as $place) {
            $placeWorkflows = $workflows
                ->where('place_type_id', $place->place_type_id)
                ->values();

            if ($placeWorkflows->isEmpty()) {
                continue;
            }

            // A few deliberately complete places for UI/completeness testing.
            // Most places remain intentionally incomplete at roughly 18-85%.
            $coverage = $place->index < 4
                ? 1.0
                : (0.18 + ((($place->index * 17) % 68) / 100));

            $targetCount = $place->index < 4
                ? $placeWorkflows->count()
                : max(1, (int) floor($placeWorkflows->count() * $coverage));

            // Rotate through the workflow catalogue so incomplete places do not
            // all contain the same first handful of features.
            $rotation = $place->index % $placeWorkflows->count();
            $ordered = $placeWorkflows
                ->slice($rotation)
                ->concat($placeWorkflows->take($rotation))
                ->take($targetCount)
                ->values();

            foreach ($ordered as $featureIndex => $workflow) {
                $contributor = $users[($place->index + $featureIndex + 2) % count($users)];
                $config = json_decode((string) $workflow->config, true) ?: [];
                $statusOptions = collect($config['status_options'] ?? [])
                    ->pluck('value')
                    ->filter(fn ($value) => is_string($value) && $value !== 'unknown')
                    ->values();

                if ($statusOptions->isEmpty()) {
                    continue;
                }

                $status = (string) $statusOptions[
                    ($place->index + ($featureIndex * 3)) % $statusOptions->count()
                ];

                $metadata = $this->demoFeatureMetadata(
                    $config,
                    $status,
                    $place->index,
                    $featureIndex,
                );

                $when = Carbon::parse($place->created_at)
                    ->addDays(2 + (($featureIndex * 5) % 45))
                    ->setTime(10 + ($featureIndex % 7), ($featureIndex * 7) % 60);

                $placeFeatureId = (int) DB::table('place_features')->insertGetId([
                    'place_id' => $place->id,
                    'feature_id' => $workflow->feature_id,
                    'feature_option_id' => null,
                    'status' => $status,
                    'value_number' => null,
                    'value_text' => null,
                    'unit_key' => null,
                    'metadata' => $metadata === []
                        ? null
                        : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'is_active' => true,
                    'internal_comment' => 'Demo: von '.$contributor->handle.' als Community-Information beigetragen.',
                    'valid_from' => $when,
                    'valid_until' => null,
                    'created_at' => $when,
                    'updated_at' => $when,
                ]);

                if (($place->index + $featureIndex) % 11 === 0) {
                    DB::table('place_feature_notes')->insert([
                        'place_feature_id' => $placeFeatureId,
                        'locale' => 'de',
                        'note' => $this->demoFeatureComment((string) $workflow->slug, $status),
                        'is_active' => true,
                        'internal_comment' => 'Demo: Nutzerkommentar zu einem Merkmal.',
                        'created_at' => $when,
                        'updated_at' => $when,
                    ]);
                }

                DB::table('audit_logs')->insert([
                    'user_id' => $contributor->id,
                    'entity_type' => 'place_feature',
                    'entity_id' => $placeFeatureId,
                    'action' => 'place_feature_added',
                    'source' => 'user',
                    'old_values' => null,
                    'new_values' => json_encode([
                        'place_id' => $place->id,
                        'feature_id' => (int) $workflow->feature_id,
                        'status' => $status,
                        'demo' => true,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'internal_comment' => 'Automatisch erzeugter usergenerierter Demo-Beitrag.',
                    'created_at' => $when,
                ]);
            }
        }
    }

    private function seedOpeningHours(array $users, array $places): void
    {
        foreach ($places as $place) {
            $creator = $users[$place->index % count($users)];
            $createdAt = Carbon::parse($place->created_at)->addDays(1);

            $seasonal = in_array($place->type_slug, ['campground', 'tent-site'], true)
                && $place->index % 4 === 0;

            $periodId = (int) DB::table('opening_hour_periods')->insertGetId([
                'place_id' => $place->id,
                'period_uuid' => (string) Str::uuid(),
                'is_year_round' => ! $seasonal,
                'start_month' => $seasonal ? 4 : null,
                'start_day' => $seasonal ? 1 : null,
                'end_month' => $seasonal ? 10 : null,
                'end_day' => $seasonal ? 31 : null,
                'is_active' => true,
                'version_valid_from' => $createdAt,
                'version_valid_until' => null,
                'internal_comment' => 'Demo: reproduzierbare Öffnungszeiten.',
                'created_by' => $creator->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach (range(1, 7) as $weekday) {
                $alwaysOpen = in_array($place->type_slug, ['parking', 'hiking-parking', 'rest-area', 'free-pitch'], true)
                    || ($place->type_slug === 'service-station' && $place->index % 2 === 0);

                $closed = ! $alwaysOpen
                    && $weekday === 7
                    && in_array($place->type_slug, ['service-station', 'camping-outdoor'], true);

                $opensAt = null;
                $closesAt = null;

                if (! $alwaysOpen && ! $closed) {
                    [$opensAt, $closesAt] = match ($place->type_slug) {
                        'campground', 'tent-site' => ['08:00:00', '20:00:00'],
                        'motorhome-pitch' => ['06:00:00', '22:00:00'],
                        'service-station' => ['07:00:00', '19:00:00'],
                        'camping-outdoor' => ['09:00:00', '18:00:00'],
                        default => ['06:00:00', '22:00:00'],
                    };
                }

                DB::table('opening_hours')->insert([
                    'place_id' => $place->id,
                    'period_id' => $periodId,
                    'feature_id' => null,
                    'day_type' => 'weekday',
                    'weekday' => $weekday,
                    'opens_at' => $opensAt,
                    'closes_at' => $closesAt,
                    'is_closed' => $closed,
                    'is_24_hours' => $alwaysOpen,
                    'by_appointment_only' => false,
                    'valid_from' => null,
                    'valid_until' => null,
                    'is_active' => true,
                    'internal_comment' => 'Demo: Wochenplan.',
                    'created_by' => $creator->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }

    private function seedPrices(array $users, array $places): void
    {
        $productIds = DB::table('price_products')->where('is_active', true)->pluck('id', 'slug');
        $billingIds = DB::table('price_billing_units')->where('is_active', true)->pluck('id', 'slug');
        $featureIds = DB::table('features')->where('is_active', true)->pluck('id', 'slug');
        $eurUnitId = DB::table('units')->whereRaw('UPPER(unit_key) = ?', ['EUR'])->value('id');

        if (! $eurUnitId || $productIds->isEmpty() || $billingIds->isEmpty()) {
            throw new \RuntimeException('Structured price reference data is missing. Run the normal database seeders first.');
        }

        foreach ($places as $place) {
            $creator = $users[($place->index + 1) % count($users)];
            $createdAt = Carbon::parse($place->created_at)->addDays(3);

            $sourceType = 'product';
            $productSlug = match ($place->type_slug) {
                'tent-site' => 'tent-pitch',
                'camping-outdoor' => 'other',
                'service-station' => null,
                default => 'motorhome-pitch',
            };
            $priceProductId = $productSlug !== null ? ($productIds[$productSlug] ?? null) : null;
            $featureId = null;
            $variantId = null;
            $customProductName = null;
            $displayName = null;

            if ($place->type_slug === 'service-station') {
                $sourceType = 'feature';
                $featureId = $featureIds['fresh-water'] ?? null;
                $displayName = 'Frischwasser';
            } elseif ($place->type_slug === 'camping-outdoor') {
                $customProductName = 'Werkstatt-/Serviceleistung';
                $displayName = 'Werkstatt-/Serviceleistung';
            } elseif ($priceProductId) {
                $variantId = DB::table('price_product_variants')
                    ->where('price_product_id', $priceProductId)
                    ->where('slug', 'standard')
                    ->value('id');
            }

            if (($sourceType === 'product' && ! $priceProductId)
                || ($sourceType === 'feature' && ! $featureId)) {
                continue;
            }

            $offerId = (int) DB::table('place_price_offers')->insertGetId([
                'place_id' => $place->id,
                'offer_uuid' => (string) Str::uuid(),
                'source_type' => $sourceType,
                'price_product_id' => $priceProductId,
                'feature_id' => $featureId,
                'price_product_variant_id' => $variantId,
                'custom_product_name' => $customProductName,
                'custom_variant_name' => null,
                'display_name' => $displayName,
                'min_vehicle_length_m' => null,
                'max_vehicle_length_m' => null,
                'min_age' => null,
                'max_age' => null,
                'linked_offer_id' => null,
                'is_refundable' => $place->index % 3 === 0,
                'condition_text' => $place->index % 5 === 0 ? 'Demo: Preis gilt bei Direktzahlung vor Ort.' : null,
                'is_active' => true,
                'version_valid_from' => $createdAt,
                'version_valid_until' => null,
                'internal_comment' => 'Demo: strukturierter Preis.',
                'created_by' => $creator->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $seasonal = in_array($place->type_slug, ['campground', 'tent-site'], true)
                && $place->index % 3 === 0;

            $periodId = (int) DB::table('place_price_periods')->insertGetId([
                'place_price_offer_id' => $offerId,
                'period_uuid' => (string) Str::uuid(),
                'is_year_round' => ! $seasonal,
                'start_month' => $seasonal ? 4 : null,
                'start_day' => $seasonal ? 1 : null,
                'end_month' => $seasonal ? 10 : null,
                'end_day' => $seasonal ? 31 : null,
                'is_active' => true,
                'version_valid_from' => $createdAt,
                'version_valid_until' => null,
                'internal_comment' => 'Demo: Preiszeitraum.',
                'created_by' => $creator->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $free = in_array($place->type_slug, ['parking', 'hiking-parking', 'rest-area', 'free-pitch'], true)
                && $place->index % 3 !== 0;

            if ($place->type_slug === 'service-station') {
                $free = $place->index % 3 === 0;
                $status = $free ? 'free' : 'fixed';
                $amount = $free ? null : round(1 + (($place->index % 4) * 0.5), 2);
                $billingUnitId = $billingIds['per-use'] ?? null;
            } elseif ($place->type_slug === 'camping-outdoor') {
                $status = $place->index % 3 === 0 ? 'from' : 'fixed';
                $amount = round(25 + (($place->index % 6) * 7.5), 2);
                $billingUnitId = $billingIds['per-hour'] ?? ($billingIds['per-use'] ?? null);
            } else {
                $status = $free ? 'free' : ($place->index % 4 === 0 ? 'from' : 'fixed');
                $amount = $free
                    ? null
                    : round(7.5 + (($place->index * 3) % 24) + (($place->index % 2) * 0.5), 2);
                $billingUnitId = $billingIds['per-pitch-night'] ?? null;
            }

            DB::table('place_price_lines')->insert([
                'place_price_period_id' => $periodId,
                'price_status' => $status,
                'amount' => $amount,
                'currency_unit_id' => $free ? null : $eurUnitId,
                'rate_quantity' => $free ? null : 1,
                'price_billing_unit_id' => $billingUnitId,
                'sort_order' => 10,
                'is_active' => true,
                'version_valid_from' => $createdAt,
                'version_valid_until' => null,
                'internal_comment' => 'Demo: Preiszeile.',
                'created_by' => $creator->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if (in_array($place->type_slug, ['campground', 'motorhome-pitch'], true)
                && isset($productIds['pet'])
                && isset($billingIds['per-night'])) {
                $petVariantId = DB::table('price_product_variants')
                    ->where('price_product_id', $productIds['pet'])
                    ->where('slug', 'dog')
                    ->value('id');

                $petOfferId = (int) DB::table('place_price_offers')->insertGetId([
                    'place_id' => $place->id,
                    'offer_uuid' => (string) Str::uuid(),
                    'source_type' => 'product',
                    'price_product_id' => $productIds['pet'],
                    'feature_id' => null,
                    'price_product_variant_id' => $petVariantId,
                    'custom_product_name' => null,
                    'custom_variant_name' => null,
                    'display_name' => null,
                    'min_vehicle_length_m' => null,
                    'max_vehicle_length_m' => null,
                    'min_age' => null,
                    'max_age' => null,
                    'linked_offer_id' => null,
                    'is_refundable' => false,
                    'condition_text' => null,
                    'is_active' => true,
                    'version_valid_from' => $createdAt,
                    'version_valid_until' => null,
                    'internal_comment' => 'Demo: Hundepreis.',
                    'created_by' => $creator->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $petPeriodId = (int) DB::table('place_price_periods')->insertGetId([
                    'place_price_offer_id' => $petOfferId,
                    'period_uuid' => (string) Str::uuid(),
                    'is_year_round' => true,
                    'start_month' => null,
                    'start_day' => null,
                    'end_month' => null,
                    'end_day' => null,
                    'is_active' => true,
                    'version_valid_from' => $createdAt,
                    'version_valid_until' => null,
                    'internal_comment' => 'Demo: ganzjähriger Hundepreis.',
                    'created_by' => $creator->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                DB::table('place_price_lines')->insert([
                    'place_price_period_id' => $petPeriodId,
                    'price_status' => $place->index % 5 === 0 ? 'included' : 'fixed',
                    'amount' => $place->index % 5 === 0 ? null : 2 + ($place->index % 4),
                    'currency_unit_id' => $place->index % 5 === 0 ? null : $eurUnitId,
                    'rate_quantity' => $place->index % 5 === 0 ? null : 1,
                    'price_billing_unit_id' => $billingIds['per-night'],
                    'sort_order' => 20,
                    'is_active' => true,
                    'version_valid_from' => $createdAt,
                    'version_valid_until' => null,
                    'internal_comment' => 'Demo: Hundepreiszeile.',
                    'created_by' => $creator->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }

    private function demoFeatureMetadata(
        array $config,
        string $status,
        int $placeIndex,
        int $featureIndex,
    ): array {
        $metadata = [];

        foreach ($config['details'] ?? [] as $detailIndex => $detail) {
            if (! in_array($status, $detail['show_for'] ?? [], true)) {
                continue;
            }

            // Not every available detail is filled. Even a place with 100%
            // known feature statuses can therefore contain realistic partial
            // sub-details.
            if ((($placeIndex + $featureIndex + $detailIndex) % 4) === 0) {
                continue;
            }

            $key = $detail['key'] ?? null;
            $type = $detail['type'] ?? null;
            if (! $key || ! $type) {
                continue;
            }

            if ($type === 'number') {
                $metadata[$key] = match ($key) {
                    'amperage' => [6, 10, 16][$featureIndex % 3],
                    'power_kw' => [3.7, 11, 22][$featureIndex % 3],
                    default => round(0.5 + ((($placeIndex + $featureIndex) % 35) / 10), 1),
                };
                continue;
            }

            if ($type === 'select') {
                $values = collect($detail['options'] ?? [])
                    ->pluck('value')
                    ->filter(fn ($value) => is_string($value) && $value !== 'unknown')
                    ->values();

                if ($values->isNotEmpty()) {
                    $metadata[$key] = $values[($placeIndex + $featureIndex) % $values->count()];
                }
                continue;
            }

            if ($type === 'number_unit') {
                $units = collect($detail['units'] ?? [])->pluck('value')->filter()->values();
                $metadata[$key] = [
                    'value' => 1 + (($placeIndex + $featureIndex) % 4),
                    'unit' => $units->first(),
                ];
                continue;
            }

            if ($type === 'pricing') {
                if ((($placeIndex + $featureIndex) % 3) === 0) {
                    $metadata[$key] = ['status' => 'free'];
                    continue;
                }

                $units = collect($detail['units'] ?? [])->pluck('value')->filter()->values();
                $metadata[$key] = [
                    'status' => 'paid',
                    'amount' => round(0.5 + ((($placeIndex + $featureIndex) % 12) * 0.5), 2),
                    'currency' => 'EUR',
                    'quantity' => 1,
                    'unit' => $units->first(),
                ];
                continue;
            }

            if ($type === 'money') {
                $units = collect($detail['units'] ?? [])->pluck('value')->filter()->values();
                $metadata[$key] = [
                    'status' => 'paid',
                    'amount' => round(6 + ((($placeIndex * 3 + $featureIndex) % 25) * 0.75), 2),
                    'currency' => 'EUR',
                    'quantity' => 1,
                    'unit' => $units->first(),
                ];
            }
        }

        return $metadata;
    }

    private function demoFeatureComment(string $slug, string $status): string
    {
        return match ($slug) {
            'electricity' => 'Bei meinem Besuch waren die Anschlüsse gut erreichbar.',
            'fresh-water' => 'Wasserstelle war vor Ort leicht zu finden.',
            'grey-water-disposal', 'black-water-disposal', 'floor-drain' => 'Entsorgungsbereich wirkte bei meinem Besuch ordentlich.',
            'toilet', 'shower' => 'Sanitärbereich war bei meinem Besuch geöffnet.',
            'wifi' => 'Empfang war auf meinem Stellplatz brauchbar.',
            'motorhome-access' => 'Zufahrt mit meinem Fahrzeug ohne besondere Probleme.',
            default => 'Community-Hinweis aus einem Demo-Besuch; Status: '.$status.'.',
        };
    }

    private function seedReviews(array $users, array $places): void
    {
        foreach ($places as $place) {
            $creatorIndex = $place->index % count($users);
            $reviewCount = 5 + ($place->index % 5);

            for ($reviewIndex = 0; $reviewIndex < $reviewCount; $reviewIndex++) {
                $reviewerIndex = ($creatorIndex + 1 + $reviewIndex) % count($users);
                $reviewer = $users[$reviewerIndex];

                $currentDate = now()
                    ->subMonths(1 + (($place->index + ($reviewIndex * 2)) % 8))
                    ->addDays(($place->index + ($reviewIndex * 3)) % 18)
                    ->setTime(12 + ($reviewIndex % 5), 10);

                if ($currentDate->gte(now())) {
                    $currentDate = now()->subDays(10 + $reviewIndex)->setTime(12, 10);
                }

                $ratings = $this->ratingsFor($place->index, $reviewIndex);
                $score = $this->score($ratings);
                $text = (($place->index + $reviewIndex) % 5 === 0)
                    ? null
                    : $this->reviewText($score, $place->index, $reviewIndex);

                $reviewId = (int) DB::table('place_reviews')->insertGetId([
                    'place_id' => $place->id,
                    'user_id' => $reviewer->id,
                    'current_version_id' => null,
                    'status' => 'active',
                    'current_published_at' => $currentDate,
                    'current_expires_at' => $currentDate->copy()->addMonthsNoOverflow(12),
                    'verified_visit' => (($place->index + $reviewIndex) % 3) === 0,
                    'verified_at' => (($place->index + $reviewIndex) % 3) === 0 ? $currentDate->copy()->addHour() : null,
                    'created_at' => $currentDate,
                    'updated_at' => $currentDate,
                ]);

                $versionNumber = 1;

                if ($place->index % 3 === 0 && $reviewIndex < 2) {
                    if ($place->index % 9 === 0 && $reviewIndex === 0) {
                        $veryOldFrom = $currentDate->copy()->subMonthsNoOverflow(8);
                        $middleFrom = $currentDate->copy()->subMonthsNoOverflow(4);
                        $veryOldRatings = $this->shiftRatings($ratings, $place->index % 2 === 0 ? -2 : 2);
                        $middleRatings = $this->shiftRatings($ratings, $place->index % 2 === 0 ? -1 : 1);

                        $this->insertReviewVersion(
                            $reviewId,
                            $versionNumber++,
                            $veryOldRatings,
                            $this->reviewText($this->score($veryOldRatings), $place->index, $reviewIndex, true),
                            $veryOldFrom,
                            $middleFrom,
                        );

                        $this->insertReviewVersion(
                            $reviewId,
                            $versionNumber++,
                            $middleRatings,
                            $this->reviewText($this->score($middleRatings), $place->index, $reviewIndex, true),
                            $middleFrom,
                            $currentDate,
                        );
                    } else {
                        $oldFrom = $currentDate->copy()->subMonthsNoOverflow(4);
                        $oldRatings = $this->shiftRatings($ratings, $place->index % 2 === 0 ? -1 : 1);

                        $this->insertReviewVersion(
                            $reviewId,
                            $versionNumber++,
                            $oldRatings,
                            $this->reviewText($this->score($oldRatings), $place->index, $reviewIndex, true),
                            $oldFrom,
                            $currentDate,
                        );
                    }
                }

                $currentVersionId = $this->insertReviewVersion(
                    $reviewId,
                    $versionNumber,
                    $ratings,
                    $text,
                    $currentDate,
                    $currentDate->copy()->addMonthsNoOverflow(12),
                );

                DB::table('place_reviews')
                    ->where('id', $reviewId)
                    ->update(['current_version_id' => $currentVersionId]);

                $this->insertReviewXp($reviewer->id, $place, $reviewId, $ratings, $currentDate);
            }
        }
    }

    private function insertReviewVersion(
        int $reviewId,
        int $versionNumber,
        array $ratings,
        ?string $text,
        \DateTimeInterface $validFrom,
        \DateTimeInterface $validUntil,
    ): int {
        return (int) DB::table('place_review_versions')->insertGetId([
            'review_id' => $reviewId,
            'version_number' => $versionNumber,
            'rating_cleanliness' => $ratings['cleanliness'],
            'rating_functionality' => $ratings['functionality'],
            'rating_condition' => $ratings['condition'],
            'rating_safety' => $ratings['safety'],
            'rating_usability' => $ratings['usability'],
            'overall_score' => $this->score($ratings),
            'review_text' => $text,
            'is_public' => true,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'created_at' => $validFrom,
            'updated_at' => $validFrom,
        ]);
    }

    private function seedGamification(array $users, array $places, int $ownerId): void
    {
        $badgeIds = DB::table('badge_definitions')->pluck('id', 'slug');
        $userPlaces = collect($places)->groupBy('creator_id');

        $pathfinderCounts = [30, 45, 55, 70, 90, 110, 140, 180, 220, 260];
        $sleuthCounts = [4, 8, 12, 18, 25, 35, 50, 70, 95, 130];

        foreach ($users as $index => $user) {
            $createdPlaces = $userPlaces->get($user->id, collect());

            foreach ($createdPlaces as $place) {
                $this->insertProgressEvent(
                    $user->id,
                    $badgeIds['explorer'],
                    $place->id,
                    'place:'.$place->id,
                    ['place_name' => $place->name],
                    $place->created_at,
                );

                $this->insertXp(
                    $user->id,
                    5,
                    'place_created',
                    'demo-place-created:'.$user->id.':'.$place->id,
                    'Neuen Platz „'.$place->name.'“ veröffentlicht',
                    $place->id,
                    'place',
                    $place->id,
                    $place->created_at,
                );
            }

            $reviews = DB::table('place_reviews')
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get(['id', 'place_id', 'current_published_at']);

            foreach ($reviews as $review) {
                $placeName = DB::table('places')->where('id', $review->place_id)->value('name');

                $this->insertProgressEvent(
                    $user->id,
                    $badgeIds['connoisseur'],
                    (int) $review->place_id,
                    'place:'.$review->place_id,
                    ['review_id' => $review->id],
                    Carbon::parse($review->current_published_at),
                );
            }

            $homePlaces = $createdPlaces->values();
            for ($n = 0; $n < $pathfinderCounts[$index]; $n++) {
                $place = $homePlaces[$n % max(1, $homePlaces->count())] ?? $places[$n % count($places)];
                $when = Carbon::parse($place->created_at)->addMinutes($n + 1);

                $this->insertProgressEvent(
                    $user->id,
                    $badgeIds['pathfinder'],
                    $place->id,
                    'demo:pathfinder:'.$user->id.':'.$n,
                    ['place_name' => $place->name, 'field' => 'demo_field_'.$n],
                    $when,
                );

                $this->insertXp(
                    $user->id,
                    1,
                    'place_info',
                    'demo-place-info:'.$user->id.':'.$n,
                    'Information zu „'.$place->name.'“ beigetragen',
                    $place->id,
                    'place',
                    $place->id,
                    $when,
                );
            }

            for ($n = 0; $n < $sleuthCounts[$index]; $n++) {
                $place = $places[($index * 7 + $n) % count($places)];
                $when = now()->subDays(180 - min(175, $n))->setTime(14, 0);

                $this->insertProgressEvent(
                    $user->id,
                    $badgeIds['sleuth'],
                    $place->id,
                    'demo:sleuth:'.$user->id.':'.$n,
                    ['place_name' => $place->name, 'field' => 'demo_correction_'.$n],
                    $when,
                );

                $this->insertXp(
                    $user->id,
                    1,
                    'place_info',
                    'demo-sleuth-xp:'.$user->id.':'.$n,
                    'Platzinformation bei „'.$place->name.'“ aktualisiert',
                    $place->id,
                    'place',
                    $place->id,
                    $when,
                );
            }

            foreach ([$badgeIds['explorer'], $badgeIds['pathfinder'], $badgeIds['connoisseur'], $badgeIds['sleuth']] as $badgeId) {
                $this->unlockProgressTiers($user->id, (int) $badgeId);
            }

            foreach (['first-steps', 'first-find', 'first-opinion'] as $achievementSlug) {
                $this->unlockAchievement($user->id, (int) $badgeIds[$achievementSlug], now()->subDays(60 + $index));
            }

            if ($index >= 5) {
                for ($day = 6; $day >= 0; $day--) {
                    DB::table('user_activity_days')->insertOrIgnore([
                        'user_id' => $user->id,
                        'activity_date' => now()->subDays($day)->toDateString(),
                        'source' => 'demo_activity',
                        'created_at' => now()->subDays($day),
                    ]);
                }

                $this->unlockAchievement($user->id, (int) $badgeIds['streak-7'], now());
                $this->insertXp(
                    $user->id,
                    5,
                    'achievement',
                    'achievement-xp:'.$user->id.':streak-7',
                    'Achievement „7 Tage dabei“ freigeschaltet',
                    null,
                    'badge',
                    (int) $badgeIds['streak-7'],
                    now(),
                );
            }

            if (Carbon::parse($user->created_at)->diffInDays(now()) >= 365) {
                $this->unlockAchievement($user->id, (int) $badgeIds['one-year'], Carbon::parse($user->created_at)->addYear());
                $this->insertXp(
                    $user->id,
                    10,
                    'achievement',
                    'achievement-xp:'.$user->id.':one-year',
                    'Achievement „Ein Jahr Camperwolf“ freigeschaltet',
                    null,
                    'badge',
                    (int) $badgeIds['one-year'],
                    Carbon::parse($user->created_at)->addYear(),
                );
            }
        }

        $manualAssignments = [
            0 => 'betatester-2026',
            1 => 'betatester-2026',
            2 => 'wulfie-getroffen',
            3 => 'frueher-unterstuetzer',
            6 => 'camperwolf-treffen-2026',
        ];

        foreach ($manualAssignments as $userIndex => $slug) {
            $user = $users[$userIndex];
            $badge = DB::table('badge_definitions')->where('slug', $slug)->first();

            if (! $badge) {
                continue;
            }

            DB::table('user_badge_unlocks')->insertOrIgnore([
                'user_id' => $user->id,
                'badge_id' => $badge->id,
                'tier' => null,
                'unlock_key' => 'demo:manual:'.$user->id.':'.$badge->id,
                'award_comment' => 'Demo-Auszeichnung für die Prüfung der Profil- und Badge-Darstellung.',
                'awarded_by' => $ownerId,
                'unlocked_at' => now()->subDays(20 + $userIndex),
                'revoked_at' => null,
                'created_at' => now()->subDays(20 + $userIndex),
                'updated_at' => now()->subDays(20 + $userIndex),
            ]);

            if ((int) $badge->xp_reward !== 0) {
                $this->insertXp(
                    $user->id,
                    (int) $badge->xp_reward,
                    'manual_badge',
                    'demo-manual-badge-xp:'.$user->id.':'.$badge->id,
                    'Auszeichnung „'.$badge->name.'“ erhalten',
                    null,
                    'badge',
                    (int) $badge->id,
                    now()->subDays(20 + $userIndex),
                    $ownerId,
                );
            }

            DB::table('user_profiles')->where('user_id', $user->id)->update([
                'selected_badge_id' => $badge->id,
                'updated_at' => now(),
            ]);
        }

        foreach ($users as $user) {
            $selected = DB::table('user_profiles')->where('user_id', $user->id)->value('selected_badge_id');

            if ($selected) {
                continue;
            }

            $connoisseurId = $badgeIds['connoisseur'] ?? null;
            $hasUnlock = $connoisseurId
                ? DB::table('user_badge_unlocks')
                    ->where('user_id', $user->id)
                    ->where('badge_id', $connoisseurId)
                    ->whereNull('revoked_at')
                    ->exists()
                : false;

            if ($hasUnlock) {
                DB::table('user_profiles')->where('user_id', $user->id)->update([
                    'selected_badge_id' => $connoisseurId,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function insertReviewXp(int $userId, object $place, int $reviewId, array $ratings, \DateTimeInterface $when): void
    {
        $this->insertXp(
            $userId,
            2,
            'review',
            'review-base:'.$userId.':'.$place->id,
            'Rezension zu „'.$place->name.'“ geschrieben',
            $place->id,
            'review',
            $reviewId,
            $when,
        );

        foreach (array_keys($ratings) as $dimension) {
            $this->insertXp(
                $userId,
                1,
                'rating_dimension',
                'rating:'.$userId.':'.$place->id.':'.$dimension,
                'Bewertung für „'.$place->name.'“ abgegeben',
                $place->id,
                'place',
                $place->id,
                $when,
            );
        }
    }

    private function insertProgressEvent(
        int $userId,
        int $badgeId,
        ?int $placeId,
        string $key,
        array $metadata,
        \DateTimeInterface $when,
    ): void {
        DB::table('badge_progress_events')->insertOrIgnore([
            'user_id' => $userId,
            'badge_id' => $badgeId,
            'place_id' => $placeId,
            'contribution_key' => $key,
            'is_active' => true,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => $when,
            'updated_at' => $when,
        ]);
    }

    private function unlockProgressTiers(int $userId, int $badgeId): void
    {
        $count = (int) DB::table('badge_progress_events')
            ->where('user_id', $userId)
            ->where('badge_id', $badgeId)
            ->where('is_active', true)
            ->count();

        $tiers = DB::table('badge_tiers')
            ->where('badge_id', $badgeId)
            ->where('threshold', '<=', $count)
            ->orderBy('sort_order')
            ->get();

        foreach ($tiers as $tier) {
            DB::table('user_badge_unlocks')->insertOrIgnore([
                'user_id' => $userId,
                'badge_id' => $badgeId,
                'tier' => $tier->tier,
                'unlock_key' => 'demo:progress:'.$userId.':'.$badgeId.':'.$tier->tier,
                'award_comment' => null,
                'awarded_by' => null,
                'unlocked_at' => now()->subDays(max(1, 50 - (int) $tier->sort_order)),
                'revoked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function unlockAchievement(int $userId, int $badgeId, \DateTimeInterface $when): void
    {
        DB::table('user_badge_unlocks')->insertOrIgnore([
            'user_id' => $userId,
            'badge_id' => $badgeId,
            'tier' => null,
            'unlock_key' => 'demo:achievement:'.$userId.':'.$badgeId,
            'award_comment' => null,
            'awarded_by' => null,
            'unlocked_at' => $when,
            'revoked_at' => null,
            'created_at' => $when,
            'updated_at' => $when,
        ]);
    }

    private function insertXp(
        int $userId,
        int $xp,
        string $eventType,
        string $dedupeKey,
        string $description,
        ?int $placeId,
        ?string $sourceType,
        ?int $sourceId,
        \DateTimeInterface $when,
        ?int $awardedBy = null,
    ): void {
        DB::table('xp_ledger')->insertOrIgnore([
            'user_id' => $userId,
            'event_type' => $eventType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'place_id' => $placeId,
            'action_key' => null,
            'dedupe_key' => $dedupeKey,
            'xp' => $xp,
            'description' => $description,
            'rule_version' => (string) config('xp.rule_version', 'v1'),
            'metadata' => json_encode(['demo' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'awarded_by' => $awardedBy,
            'created_at' => $when,
        ]);
    }

    private function ratingsFor(int $placeIndex, int $reviewIndex): array
    {
        return [
            'cleanliness' => 1 + (($placeIndex + ($reviewIndex * 2)) % 5),
            'functionality' => 1 + ((($placeIndex * 2) + $reviewIndex + 1) % 5),
            'condition' => 1 + ((($placeIndex * 3) + $reviewIndex + 2) % 5),
            'safety' => 1 + ((($placeIndex * 4) + ($reviewIndex * 2) + 3) % 5),
            'usability' => 1 + ((($placeIndex * 2) + ($reviewIndex * 3) + 4) % 5),
        ];
    }

    private function shiftRatings(array $ratings, int $shift): array
    {
        return array_map(
            fn (int $value): int => max(1, min(5, $value + $shift)),
            $ratings,
        );
    }

    private function score(array $ratings): float
    {
        return round(array_sum($ratings) / count($ratings), 1);
    }

    private function reviewText(float $score, int $placeIndex, int $reviewIndex, bool $historical = false): string
    {
        $prefix = $historical ? 'Bei meinem damaligen Besuch: ' : '';

        $text = match (true) {
            $score >= 4.2 => $prefix.'Insgesamt ein sehr guter Eindruck. Der Platz war gut nutzbar und die vorhandenen Einrichtungen wirkten ordentlich gepflegt.',
            $score >= 3.2 => $prefix.'Unterm Strich okay. Einige Punkte waren wirklich gut, an anderen Stellen gibt es aber noch Luft nach oben.',
            $score >= 2.2 => $prefix.'Eher durchwachsen. Für einen kurzen Aufenthalt brauchbar, aber mehrere Dinge haben den Gesamteindruck deutlich gedrückt.',
            default => $prefix.'Bei diesem Besuch war ich nicht zufrieden. Mehrere grundlegende Punkte haben für mich nicht gut funktioniert.',
        };

        if (($placeIndex + $reviewIndex) % 4 === 0) {
            $text .= "\n\nBesonders aufgefallen ist mir die praktische Nutzbarkeit vor Ort. Ich würde bei einem späteren Besuch prüfen, ob sich daran etwas geändert hat.";
        }

        return $text;
    }
}
