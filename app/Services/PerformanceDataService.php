<?php

namespace App\Services;

use Imagick;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PerformanceDataService
{
    public const PLACE_SLUG_PREFIX = 'perf-place-';
    public const USER_EMAIL_DOMAIN = '@performance.camperwolf.test';
    public const MARKER = '[camperwolf-performance-data]';
    public const STORAGE_DIRECTORY = 'performance';

    private const CHUNK_SIZE = 500;

    public function seed(int $placeCount): array
    {
        $this->guardEnvironment();

        if (! in_array($placeCount, [10000, 25000, 50000], true)) {
            throw new RuntimeException('Erlaubte Größen sind 10000, 25000 oder 50000 Plätze.');
        }

        if ($this->hasData()) {
            throw new RuntimeException('Performance-Daten existieren bereits. Zuerst camperwolf:performance-clear ausführen.');
        }

        $placeTypes = DB::table('place_types')
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $featureIds = DB::table('features')
            ->where('is_active', true)
            ->where('is_searchable', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $vehicleTypeIds = DB::table('vehicle_types')
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $featureWorkflows = DB::table('feature_workflows')
            ->whereIn('feature_id', $featureIds)
            ->where('is_active', true)
            ->pluck('config', 'feature_id')
            ->map(fn ($config) => json_decode((string) $config, true) ?: [])
            ->all();

        if ($placeTypes === [] || $featureIds === []) {
            throw new RuntimeException('Referenzdaten fehlen. Bitte zuerst die normalen Seeder ausführen.');
        }

        $startedAt = microtime(true);
        $userCount = max(500, (int) ceil($placeCount / 4));

        $this->seedUsers($userCount);
        $userIds = DB::table('users')
            ->where('email', 'like', '%'.self::USER_EMAIL_DOMAIN)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->seedPlaces($placeCount, $placeTypes, $userIds);

        $placeIds = DB::table('places')
            ->where('slug', 'like', self::PLACE_SLUG_PREFIX.'%')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->seedAddresses($placeIds);
        $this->seedPlaceProfileData($placeIds, $userIds);
        $this->seedOpeningHours($placeIds, $userIds);
        $this->seedFeatures($placeIds, $featureIds, $featureWorkflows);
        $this->seedVehicleTypes($placeIds, $vehicleTypeIds);
        $reviewCount = $this->seedReviews($placeIds, $userIds);
        $photoCount = $this->seedPhotos($placeIds, $userIds);
        $favoriteCount = $this->seedFavorites($placeIds, $userIds);
        $priceCount = $this->seedPrices($placeIds, $userIds);

        return [
            'places' => count($placeIds),
            'users' => count($userIds),
            'reviews' => $reviewCount,
            'photos' => $photoCount,
            'favorites' => $favoriteCount,
            'price_offers' => $priceCount,
            'seconds' => round(microtime(true) - $startedAt, 2),
        ];
    }

    public function clear(): array
    {
        $this->guardEnvironment();

        $placeIds = DB::table('places')
            ->where('slug', 'like', self::PLACE_SLUG_PREFIX.'%')
            ->pluck('id');

        $userIds = DB::table('users')
            ->where('email', 'like', '%'.self::USER_EMAIL_DOMAIN)
            ->pluck('id');

        $photoIds = DB::table('photos')
            ->where('internal_comment', self::MARKER)
            ->pluck('id');

        $counts = [
            'places' => $placeIds->count(),
            'users' => $userIds->count(),
            'photos' => $photoIds->count(),
        ];

        DB::transaction(function () use ($placeIds, $userIds, $photoIds): void {
            if ($placeIds->isNotEmpty()) {
                DB::table('places')->whereIn('id', $placeIds)->delete();
            }

            if ($photoIds->isNotEmpty()) {
                DB::table('photos')->whereIn('id', $photoIds)->delete();
            }

            if ($userIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $userIds)->delete();
            }
        });

        Storage::disk('local')->deleteDirectory(self::STORAGE_DIRECTORY);

        return $counts;
    }

    public function hasData(): bool
    {
        return DB::table('places')
            ->where('slug', 'like', self::PLACE_SLUG_PREFIX.'%')
            ->exists()
            || DB::table('users')
                ->where('email', 'like', '%'.self::USER_EMAIL_DOMAIN)
                ->exists();
    }

    private function seedUsers(int $count): void
    {
        $now = now();
        $password = Hash::make(Str::random(32));

        for ($offset = 0; $offset < $count; $offset += self::CHUNK_SIZE) {
            $rows = [];
            $profiles = [];
            $settings = [];

            $limit = min($count, $offset + self::CHUNK_SIZE);
            for ($i = $offset; $i < $limit; $i++) {
                $n = $i + 1;
                $createdAt = $now->copy()->subDays($n % 1200);
                $rows[] = [
                    'name' => 'Performance User '.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
                    'email' => 'perf-user-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT).self::USER_EMAIL_DOMAIN,
                    'locale' => $n % 8 === 0 ? 'en' : 'de',
                    'profile_photo_id' => null,
                    'email_verified_at' => $createdAt,
                    'password' => $password,
                    'remember_token' => null,
                    'last_seen_at' => $now->copy()->subMinutes($n % 10080),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            DB::table('users')->insert($rows);
        }

        $users = DB::table('users')
            ->where('email', 'like', '%'.self::USER_EMAIL_DOMAIN)
            ->orderBy('id')
            ->get(['id', 'created_at']);

        foreach ($users->chunk(self::CHUNK_SIZE) as $chunk) {
            $profiles = [];
            $settings = [];
            foreach ($chunk as $index => $user) {
                $profiles[] = [
                    'user_id' => $user->id,
                    'public_handle' => 'perf'.str_pad((string) $user->id, 10, '0', STR_PAD_LEFT),
                    'handle_finalized_at' => $user->created_at,
                    'bio' => null,
                    'hometown_city' => null,
                    'hometown_country_code' => 'DE',
                    'hometown_latitude' => null,
                    'hometown_longitude' => null,
                    'hometown_source_id' => null,
                    'birth_date' => null,
                    'gender' => null,
                    'gender_custom' => null,
                    'vehicle_type' => null,
                    'vehicle_details' => null,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->created_at,
                ];
                $settings[] = [
                    'user_id' => $user->id,
                    'show_real_name' => false,
                    'show_reviews_in_profile' => true,
                    'show_photos_in_profile' => true,
                    'show_join_date' => true,
                    'show_activity_counts' => true,
                    'allow_email_notifications' => true,
                    'profile_photo_visibility' => 'public',
                    'bio_visibility' => 'registered',
                    'hometown_visibility' => 'registered',
                    'age_visibility' => 'private',
                    'gender_visibility' => 'private',
                    'vehicle_visibility' => 'registered',
                    'social_links_visibility' => 'registered',
                    'show_gamification' => true,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->created_at,
                ];
            }

            DB::table('user_profiles')->insert($profiles);
            DB::table('user_settings')->insert($settings);
        }
    }

    private function seedPlaces(int $count, array $placeTypes, array $userIds): void
    {
        $now = now();
        $cities = [
            ['Essen', 'DE', 51.4556, 7.0116],
            ['Hamburg', 'DE', 53.5511, 9.9937],
            ['Berlin', 'DE', 52.5200, 13.4050],
            ['München', 'DE', 48.1351, 11.5820],
            ['Köln', 'DE', 50.9375, 6.9603],
            ['Leipzig', 'DE', 51.3397, 12.3731],
            ['Freiburg', 'DE', 47.9990, 7.8421],
            ['Hannover', 'DE', 52.3759, 9.7320],
            ['Dresden', 'DE', 51.0504, 13.7373],
            ['Kassel', 'DE', 51.3127, 9.4797],
            ['Venlo', 'NL', 51.3704, 6.1724],
            ['Strasbourg', 'FR', 48.5734, 7.7521],
        ];

        for ($offset = 0; $offset < $count; $offset += self::CHUNK_SIZE) {
            $rows = [];
            $limit = min($count, $offset + self::CHUNK_SIZE);

            for ($i = $offset; $i < $limit; $i++) {
                $n = $i + 1;
                [$city, $country, $lat, $lon] = $cities[$i % count($cities)];
                $lat += (($n % 101) - 50) / 1000;
                $lon += (($n % 89) - 44) / 1000;
                $createdAt = $now->copy()->subDays($n % 900)->subMinutes($n % 1440);

                $rows[] = [
                    'place_type_id' => $placeTypes[$i % count($placeTypes)],
                    'name' => 'Performance Platz '.str_pad((string) $n, 6, '0', STR_PAD_LEFT).' '.$city,
                    'slug' => self::PLACE_SLUG_PREFIX.str_pad((string) $n, 6, '0', STR_PAD_LEFT),
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'publication_status' => $n % 40 === 0 ? 'pending' : 'published',
                    'legal_status' => $n % 9 === 0 ? 'unclear' : 'allowed',
                    'opening_status' => $n % 13 === 0 ? 'seasonal' : 'open',
                    'is_active' => $n % 100 !== 0,
                    'internal_comment' => self::MARKER,
                    'created_by' => $userIds[$i % count($userIds)],
                    'approved_by' => null,
                    'approved_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            DB::table('places')->insert($rows);
        }
    }

    private function seedAddresses(array $placeIds): void
    {
        $now = now();
        $cities = ['Essen', 'Hamburg', 'Berlin', 'München', 'Köln', 'Leipzig', 'Freiburg', 'Hannover', 'Dresden', 'Kassel'];

        foreach (array_chunk($placeIds, self::CHUNK_SIZE) as $chunkIndex => $chunk) {
            $rows = [];
            foreach ($chunk as $index => $placeId) {
                $n = ($chunkIndex * self::CHUNK_SIZE) + $index + 1;
                $rows[] = [
                    'place_id' => $placeId,
                    'country_code' => $n % 20 === 0 ? 'NL' : 'DE',
                    'region_id' => null,
                    'postal_code' => str_pad((string) (10000 + ($n % 89999)), 5, '0', STR_PAD_LEFT),
                    'city' => $cities[$n % count($cities)],
                    'street' => 'Performanceweg',
                    'house_number' => (string) (($n % 199) + 1),
                    'address_addition' => null,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::MARKER,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('place_addresses')->insert($rows);
        }
    }

    private function seedPlaceProfileData(array $placeIds, array $userIds): void
    {
        $now = now();
        $translationBuffer = [];
        $detailBuffer = [];
        $contactBuffer = [];

        foreach ($placeIds as $index => $placeId) {
            if ($index % 10 !== 0) {
                $translationBuffer[] = [
                    'place_id' => $placeId,
                    'locale' => 'de',
                    'description' => 'Automatisch erzeugter Performance-Platz für reproduzierbare Last- und Querytests.',
                    'directions' => $index % 4 === 0 ? 'Zufahrt für Performance-Testdaten.' : null,
                    'access_information' => $index % 6 === 0 ? 'Zugangsinformation für Performance-Testdaten.' : null,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::MARKER,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($index % 5 !== 0) {
                $detailBuffer[] = [
                    'place_id' => $placeId,
                    'operator_name' => $index % 3 === 0 ? 'Performance Betreiber '.(($index % 500) + 1) : null,
                    'pitch_count' => 5 + ($index % 180),
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::MARKER,
                    'created_by' => $userIds[$index % count($userIds)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($index % 3 === 0) {
                $contactBuffer[] = [
                    'place_id' => $placeId,
                    'contact_type' => 'website',
                    'value' => 'https://performance.invalid/place/'.$placeId,
                    'sort_order' => 10,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::MARKER,
                    'created_by' => $userIds[$index % count($userIds)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($translationBuffer) >= self::CHUNK_SIZE) {
                DB::table('place_translations')->insert($translationBuffer);
                $translationBuffer = [];
            }
            if (count($detailBuffer) >= self::CHUNK_SIZE) {
                DB::table('place_details')->insert($detailBuffer);
                $detailBuffer = [];
            }
            if (count($contactBuffer) >= self::CHUNK_SIZE) {
                DB::table('place_contacts')->insert($contactBuffer);
                $contactBuffer = [];
            }
        }

        if ($translationBuffer !== []) {
            DB::table('place_translations')->insert($translationBuffer);
        }
        if ($detailBuffer !== []) {
            DB::table('place_details')->insert($detailBuffer);
        }
        if ($contactBuffer !== []) {
            DB::table('place_contacts')->insert($contactBuffer);
        }
    }

    private function seedOpeningHours(array $placeIds, array $userIds): void
    {
        $now = now();
        $buffer = [];

        foreach ($placeIds as $index => $placeId) {
            if ($index % 3 === 0) {
                continue;
            }

            foreach ([1, 3, 5, 6, 7] as $weekday) {
                $buffer[] = [
                    'place_id' => $placeId,
                    'feature_id' => null,
                    'day_type' => 'weekday',
                    'weekday' => $weekday,
                    'opens_at' => '08:00:00',
                    'closes_at' => $weekday >= 6 ? '20:00:00' : '18:00:00',
                    'is_closed' => false,
                    'is_24_hours' => $index % 11 === 0,
                    'by_appointment_only' => false,
                    'valid_from' => null,
                    'valid_until' => null,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::MARKER,
                    'created_by' => $userIds[$index % count($userIds)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($buffer) >= self::CHUNK_SIZE) {
                    DB::table('opening_hours')->insert($buffer);
                    $buffer = [];
                }
            }
        }

        if ($buffer !== []) {
            DB::table('opening_hours')->insert($buffer);
        }
    }

    private function seedFeatures(array $placeIds, array $featureIds, array $featureWorkflows): void
    {
        $now = now();
        $buffer = [];

        foreach ($placeIds as $placeIndex => $placeId) {
            $featureCount = 10 + ($placeIndex % min(18, max(1, count($featureIds))));
            $featureCount = min($featureCount, count($featureIds));

            for ($j = 0; $j < $featureCount; $j++) {
                $featureId = $featureIds[($placeIndex + $j) % count($featureIds)];
                $workflow = $featureWorkflows[$featureId] ?? [];
                $details = $workflow['details'] ?? [];
                $statusOptions = collect($workflow['status_options'] ?? [])->pluck('value')->filter()->values()->all();
                $detailStatus = collect($details)
                    ->flatMap(fn ($detail) => $detail['show_for'] ?? [])
                    ->filter()
                    ->first();
                $status = (($placeIndex + $j) % 17 === 0)
                    ? 'unknown'
                    : ($detailStatus ?: ($statusOptions[1] ?? 'available'));
                $metadata = $this->featureMetadata($details, $placeIndex, $j);

                $buffer[] = [
                    'place_id' => $placeId,
                    'feature_id' => $featureId,
                    'feature_option_id' => null,
                    'status' => $status,
                    'value_number' => null,
                    'value_text' => null,
                    'unit_key' => null,
                    'unit_id' => null,
                    'metadata' => $metadata === [] ? null : json_encode($metadata),
                    'valid_from' => $now,
                    'valid_until' => null,
                    'is_active' => true,
                    'internal_comment' => self::MARKER,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($buffer) >= self::CHUNK_SIZE) {
                    DB::table('place_features')->insert($buffer);
                    $buffer = [];
                }
            }
        }

        if ($buffer !== []) {
            DB::table('place_features')->insert($buffer);
        }
    }

    private function featureMetadata(array $details, int $placeIndex, int $detailOffset): array
    {
        $metadata = [];

        foreach ($details as $detailIndex => $detail) {
            $key = (string) ($detail['key'] ?? '');
            $type = (string) ($detail['type'] ?? '');

            if ($key === '') {
                continue;
            }

            if ($type === 'select') {
                $options = collect($detail['options'] ?? [])
                    ->pluck('value')
                    ->filter()
                    ->values()
                    ->all();

                if ($options !== []) {
                    $metadata[$key] = $options[($placeIndex + $detailOffset + $detailIndex) % count($options)];
                }
            } elseif ($type === 'number') {
                $metadata[$key] = 1 + (($placeIndex * 7 + $detailOffset * 3 + $detailIndex) % 100);
            } elseif ($type === 'number_unit') {
                $units = collect($detail['units'] ?? [])
                    ->pluck('value')
                    ->filter()
                    ->values()
                    ->all();

                $metadata[$key] = [
                    'value' => 1 + (($placeIndex * 5 + $detailOffset + $detailIndex) % 48),
                    'unit' => $units === [] ? null : $units[($placeIndex + $detailIndex) % count($units)],
                ];
            }
        }

        return $metadata;
    }

    private function seedVehicleTypes(array $placeIds, array $vehicleTypeIds): void
    {
        if ($vehicleTypeIds === []) {
            return;
        }

        $now = now();
        $buffer = [];

        foreach ($placeIds as $index => $placeId) {
            $count = min(count($vehicleTypeIds), 1 + ($index % 3));
            for ($j = 0; $j < $count; $j++) {
                $buffer[] = [
                    'place_id' => $placeId,
                    'vehicle_type_id' => $vehicleTypeIds[($index + $j) % count($vehicleTypeIds)],
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::MARKER,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($buffer) >= self::CHUNK_SIZE) {
                DB::table('place_vehicle_types')->insert($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table('place_vehicle_types')->insert($buffer);
        }
    }

    private function seedReviews(array $placeIds, array $userIds): int
    {
        $now = now();
        $buffer = [];

        foreach ($placeIds as $placeIndex => $placeId) {
            $count = $placeIndex % 5 === 0 ? 0 : 2 + ($placeIndex % 8);
            for ($j = 0; $j < $count; $j++) {
                $userId = $userIds[(($placeIndex * 11) + $j) % count($userIds)];
                $buffer[] = [
                    'place_id' => $placeId,
                    'user_id' => $userId,
                    'current_version_id' => null,
                    'status' => 'active',
                    'current_published_at' => $now,
                    'current_expires_at' => $now->copy()->addMonths(12),
                    'verified_visit' => (($placeIndex + $j) % 4 === 0),
                    'verified_at' => (($placeIndex + $j) % 4 === 0) ? $now : null,
                    'created_at' => $now->copy()->subDays(($placeIndex + $j) % 365),
                    'updated_at' => $now,
                ];

                if (count($buffer) >= self::CHUNK_SIZE) {
                    DB::table('place_reviews')->insertOrIgnore($buffer);
                    $buffer = [];
                }
            }
        }

        if ($buffer !== []) {
            DB::table('place_reviews')->insertOrIgnore($buffer);
        }

        $reviewQuery = DB::table('place_reviews as pr')
            ->join('places as p', 'p.id', '=', 'pr.place_id')
            ->where('p.slug', 'like', self::PLACE_SLUG_PREFIX.'%');

        $reviewCount = (clone $reviewQuery)->count('pr.id');

        (clone $reviewQuery)
            ->orderBy('pr.id')
            ->select(['pr.id', 'pr.place_id', 'pr.user_id'])
            ->chunkById(self::CHUNK_SIZE, function ($reviews) use ($now): void {
                $versions = [];

                foreach ($reviews as $review) {
                    $ratings = [
                        1 + (($review->id * 7) % 5),
                        1 + (($review->id * 3) % 5),
                        1 + (($review->id * 5) % 5),
                        1 + (($review->id * 11) % 5),
                        1 + (($review->id * 13) % 5),
                    ];

                    $versions[] = [
                        'review_id' => $review->id,
                        'version_number' => 1,
                        'rating_cleanliness' => $ratings[0],
                        'rating_functionality' => $ratings[1],
                        'rating_condition' => $ratings[2],
                        'rating_safety' => $ratings[3],
                        'rating_usability' => $ratings[4],
                        'overall_score' => round(array_sum($ratings) / 5, 1),
                        'review_text' => $review->id % 3 === 0
                            ? 'Automatisch erzeugte Performance-Bewertung für Last- und Querytests.'
                            : null,
                        'is_public' => true,
                        'valid_from' => $now->copy()->subDays($review->id % 300),
                        'valid_until' => $now->copy()->addMonths(12),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('place_review_versions')->insert($versions);

                $reviewIds = $reviews->pluck('id')->map(fn ($id) => (int) $id)->all();

                if ($reviewIds !== []) {
                    $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));

                    DB::update(
                        "UPDATE place_reviews pr
                         INNER JOIN place_review_versions prv
                            ON prv.review_id = pr.id
                           AND prv.version_number = 1
                         SET pr.current_version_id = prv.id
                         WHERE pr.id IN ($placeholders)",
                        $reviewIds,
                    );
                }
            }, 'pr.id', 'id');

        return (int) $reviewCount;
    }

    private function seedPhotos(array $placeIds, array $userIds): int
    {
        $this->createDummyImages();

        $now = now();
        $buffer = [];
        $photoCount = 0;

        $flush = function () use (&$buffer, $now): void {
            if ($buffer === []) {
                return;
            }

            $links = [];
            foreach ($buffer as &$row) {
                $links[$row['uuid']] = [$row['_place_id'], $row['_sort_order']];
                unset($row['_place_id'], $row['_sort_order']);
            }
            unset($row);

            DB::table('photos')->insert($buffer);

            $photoRows = DB::table('photos')
                ->whereIn('uuid', array_keys($links))
                ->get(['id', 'uuid']);

            $placePhotos = [];
            foreach ($photoRows as $photo) {
                [$placeId, $sortOrder] = $links[$photo->uuid];
                $placePhotos[] = [
                    'place_id' => $placeId,
                    'place_review_id' => null,
                    'photo_id' => $photo->id,
                    'photo_type' => 'community',
                    'sort_order' => $sortOrder,
                    'is_thumbnail_eligible' => true,
                    'thumbnail_excluded_by' => null,
                    'thumbnail_excluded_at' => null,
                    'thumbnail_exclusion_reason' => null,
                    'is_active' => true,
                    'internal_comment' => self::MARKER,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('place_photos')->insert($placePhotos);
            $buffer = [];
        };

        foreach ($placeIds as $placeIndex => $placeId) {
            if ($placeIndex % 2 !== 0) {
                continue;
            }

            $count = 1 + ($placeIndex % 6);
            for ($j = 0; $j < $count; $j++) {
                $uuid = (string) Str::uuid();
                $dummy = ($placeIndex + $j) % 10;
                $buffer[] = [
                    'uuid' => $uuid,
                    'user_id' => $userIds[(($placeIndex * 5) + $j) % count($userIds)],
                    'storage_path' => self::STORAGE_DIRECTORY.'/dummy-'.$dummy.'-detail.webp',
                    'source_path' => null,
                    'preview_path' => self::STORAGE_DIRECTORY.'/dummy-'.$dummy.'-preview.webp',
                    'original_filename' => null,
                    'mime_type' => 'image/webp',
                    'file_size' => 40000 + ($dummy * 7000),
                    'preview_file_size' => 8000 + ($dummy * 1500),
                    'width' => $dummy % 3 === 0 ? 1200 : 1600,
                    'height' => $dummy % 3 === 0 ? 1600 : 1200,
                    'status' => 'approved',
                    'moderated_by' => null,
                    'moderated_at' => $now,
                    'moderation_reason' => null,
                    'processing_error' => null,
                    'is_active' => true,
                    'internal_comment' => self::MARKER,
                    'created_at' => $now->copy()->subDays(($placeIndex + $j) % 300),
                    'updated_at' => $now,
                    '_place_id' => $placeId,
                    '_sort_order' => $j,
                ];
                $photoCount++;

                if (count($buffer) >= self::CHUNK_SIZE) {
                    $flush();
                }
            }
        }

        $flush();

        return $photoCount;
    }

    private function seedFavorites(array $placeIds, array $userIds): int
    {
        $now = now();
        $buffer = [];
        $count = 0;

        foreach ($userIds as $userIndex => $userId) {
            $favorites = 2 + ($userIndex % 8);
            for ($j = 0; $j < $favorites; $j++) {
                $placeId = $placeIds[(($userIndex * 17) + ($j * 31)) % count($placeIds)];
                $buffer[] = [
                    'user_id' => $userId,
                    'place_id' => $placeId,
                    'notify_changes' => $j % 3 !== 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $count++;
            }

            if (count($buffer) >= self::CHUNK_SIZE) {
                DB::table('place_favorites')->insertOrIgnore($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table('place_favorites')->insertOrIgnore($buffer);
        }

        return $count;
    }

    private function seedPrices(array $placeIds, array $userIds): int
    {
        $productIds = DB::table('price_products')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('is_vehicle_base_price', true)
                    ->orWhere('is_overnight_base_price', true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $currencyId = DB::table('units')->where('unit_key', 'EUR')->value('id');
        $billingUnitId = DB::table('price_billing_units')->where('is_active', true)->orderBy('id')->value('id');

        if ($productIds === [] || ! $currencyId || ! $billingUnitId) {
            return 0;
        }

        $now = now();
        $offerCount = 0;

        foreach (array_chunk($placeIds, self::CHUNK_SIZE) as $chunkIndex => $chunk) {
            $offers = [];
            foreach ($chunk as $index => $placeId) {
                $globalIndex = ($chunkIndex * self::CHUNK_SIZE) + $index;
                if ($globalIndex % 4 === 0) {
                    continue;
                }

                $offers[] = [
                    'place_id' => $placeId,
                    'offer_uuid' => (string) Str::uuid(),
                    'source_type' => 'product',
                    'price_product_id' => $productIds[$globalIndex % count($productIds)],
                    'feature_id' => null,
                    'price_product_variant_id' => null,
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
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::MARKER,
                    'created_by' => $userIds[$globalIndex % count($userIds)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $offerCount++;
            }

            if ($offers === []) {
                continue;
            }

            DB::table('place_price_offers')->insert($offers);

            $offerRows = DB::table('place_price_offers')
                ->whereIn('offer_uuid', array_column($offers, 'offer_uuid'))
                ->get(['id', 'place_id']);

            $periods = [];
            foreach ($offerRows as $offer) {
                $periods[] = [
                    'place_price_offer_id' => $offer->id,
                    'period_uuid' => (string) Str::uuid(),
                    'is_year_round' => true,
                    'start_month' => null,
                    'start_day' => null,
                    'end_month' => null,
                    'end_day' => null,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::MARKER,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('place_price_periods')->insert($periods);

            $periodRows = DB::table('place_price_periods')
                ->whereIn('period_uuid', array_column($periods, 'period_uuid'))
                ->get(['id', 'place_price_offer_id']);

            $lines = [];
            foreach ($periodRows as $period) {
                $lines[] = [
                    'place_price_period_id' => $period->id,
                    'price_status' => 'fixed',
                    'amount' => 5 + (($period->id * 7) % 55),
                    'currency_unit_id' => $currencyId,
                    'rate_quantity' => 1,
                    'price_billing_unit_id' => $billingUnitId,
                    'sort_order' => 0,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::MARKER,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('place_price_lines')->insert($lines);
        }

        return $offerCount;
    }

    private function createDummyImages(): void
    {
        Storage::disk('local')->makeDirectory(self::STORAGE_DIRECTORY);

        for ($i = 0; $i < 10; $i++) {
            $detailPath = self::STORAGE_DIRECTORY.'/dummy-'.$i.'-detail.webp';
            $previewPath = self::STORAGE_DIRECTORY.'/dummy-'.$i.'-preview.webp';

            if (Storage::disk('local')->exists($detailPath) && Storage::disk('local')->exists($previewPath)) {
                continue;
            }

            if (class_exists(Imagick::class)) {
                $detail = new Imagick();
                $detail->newImage($i % 3 === 0 ? 1200 : 1600, $i % 3 === 0 ? 1600 : 1200, sprintf('rgb(%d,%d,%d)', 40 + ($i * 17), 70 + ($i * 11), 90 + ($i * 9)));
                $detail->setImageFormat('webp');
                $detail->setImageCompressionQuality(70);
                $detail->stripImage();
                Storage::disk('local')->put($detailPath, $detail->getImageBlob());

                $detail->resizeImage(480, 360, Imagick::FILTER_LANCZOS, 1, true);
                $detail->setImageCompressionQuality(60);
                Storage::disk('local')->put($previewPath, $detail->getImageBlob());
                $detail->clear();
                $detail->destroy();
            } else {
                // Photo queries only need existing files. The production stack already
                // requires Imagick for real uploads; this fallback keeps DB benchmarks usable.
                Storage::disk('local')->put($detailPath, 'Camperwolf performance dummy '.$i);
                Storage::disk('local')->put($previewPath, 'Camperwolf performance preview '.$i);
            }
        }
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Performance-Daten dürfen ausschließlich in local/testing erzeugt oder gelöscht werden.');
        }
    }
}
