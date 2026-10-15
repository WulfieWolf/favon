<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaceSubmissionService
{
    /**
     * @param array{
     *   place_type_id:int,
     *   name:string,
     *   latitude:float|int|string,
     *   longitude:float|int|string,
     *   description?:string|null,
     *   environment?:string|null,
     *   has_gloryhole?:bool|int|string|null,
     *   street_address?:string|null,
     *   postal_code?:string|null,
     *   city?:string|null,
     *   country_code?:string|null
     * } $data
     */
    public function submit(User $user, array $data): int
    {
        return DB::transaction(function () use ($user, $data): int {
            $now = now();
            $name = trim($data['name']);
            $description = isset($data['description']) ? trim((string) $data['description']) : null;
            $countryCode = isset($data['country_code']) && $data['country_code'] !== null
                ? strtoupper(trim((string) $data['country_code']))
                : null;

            $placeId = DB::table('places')->insertGetId([
                'place_type_id' => (int) $data['place_type_id'],
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'description' => $description !== '' ? $description : null,
                'environment' => $data['environment'] ?? null,
                'has_gloryhole' => array_key_exists('has_gloryhole', $data) && $data['has_gloryhole'] !== null
                    ? (bool) $data['has_gloryhole']
                    : null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'publication_status' => 'pending',
                'legal_status' => 'unclear',
                'opening_status' => 'unclear',
                'is_active' => true,
                'internal_comment' => null,
                'created_by' => $user->id,
                'approved_by' => null,
                'approved_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('place_place_types')->insert([
                'place_id' => $placeId,
                'place_type_id' => (int) $data['place_type_id'],
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            [$street, $houseNumber] = $this->splitStreetAddress($data['street_address'] ?? null);

            $address = [
                'country_code' => $countryCode,
                'postal_code' => $this->nullableTrim($data['postal_code'] ?? null),
                'city' => $this->nullableTrim($data['city'] ?? null),
                'street' => $street,
                'house_number' => $houseNumber,
            ];

            if (collect($address)->contains(fn ($value) => $value !== null)) {
                DB::table('place_addresses')->insert([
                    'place_id' => $placeId,
                    'country_code' => $address['country_code'],
                    'postal_code' => $address['postal_code'],
                    'city' => $address['city'],
                    'street' => $address['street'],
                    'house_number' => $address['house_number'],
                    'address_addition' => null,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $placeId;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'place';
        }

        do {
            $slug = $base.'-'.Str::lower(Str::random(8));
        } while (DB::table('places')->where('slug', $slug)->exists());

        return $slug;
    }

    private function splitStreetAddress(mixed $value): array
    {
        $address = $this->nullableTrim($value);

        if ($address === null) {
            return [null, null];
        }

        if (preg_match('/^(.+?)\s+([0-9]+[A-Za-z]?(?:[-\/]?[0-9A-Za-z]+)?)$/u', $address, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return [$address, null];
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
