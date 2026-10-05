<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ChangeRequestTestSeeder extends Seeder
{
    public const TEST_USER_EMAIL = 'change-request-test@camperwolf.local';
    public const TEST_PLACE_SLUG = 'test-change-request-camp';
    public const TEST_MARKER = '[TEST:change-request-seeder]';

    public function run(): void
    {
        $this->guardEnvironment();

        // Make repeated runs deterministic and fully removable.
        $this->call(RemoveChangeRequestTestDataSeeder::class);

        DB::transaction(function (): void {
            $placeTypeId = DB::table('place_types')
                ->where('slug', 'stay')
                ->where('is_active', true)
                ->value('id');

            if (! $placeTypeId) {
                throw new RuntimeException('Active place type "stay" is required. Run the normal seeders first.');
            }

            $fields = collect([
                'places.name',
                'places.legal_status',
                'places.opening_status',
                'place_addresses.street',
                'place_addresses.house_number',
                'place_addresses.city',
                'place_details.pitch_count',
            ])->mapWithKeys(function (string $key) {
                [$table, $field] = explode('.', $key, 2);

                $id = DB::table('suggestable_fields')
                    ->where('target_table', $table)
                    ->where('target_field', $field)
                    ->where('is_active', true)
                    ->where('is_suggestable', true)
                    ->value('id');

                if (! $id) {
                    throw new RuntimeException("Suggestable field {$key} is missing or inactive.");
                }

                return [$key => $id];
            });

            $now = now();

            $testUserId = DB::table('users')->insertGetId([
                'name' => 'Camperwolf Testuser',
                'email' => self::TEST_USER_EMAIL,
                'locale' => 'de',
                'email_verified_at' => $now,
                'password' => Hash::make(Str::random(48)),
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $placeId = DB::table('places')->insertGetId([
                'place_type_id' => $placeTypeId,
                'name' => 'Testcamp am Beispielwald',
                'slug' => self::TEST_PLACE_SLUG,
                'latitude' => 51.4556430,
                'longitude' => 7.0115550,
                'publication_status' => 'published',
                'legal_status' => 'unclear',
                'opening_status' => 'open',
                'is_active' => true,
                'internal_comment' => self::TEST_MARKER.' Nur für lokale Change-Request-Tests.',
                'created_by' => $testUserId,
                'approved_by' => null,
                'approved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $addressId = DB::table('place_addresses')->insertGetId([
                'place_id' => $placeId,
                'country_code' => 'DE',
                'region_id' => null,
                'postal_code' => '45127',
                'city' => 'Essen',
                'street' => 'Beispielstraße',
                'house_number' => '10',
                'address_addition' => null,
                'is_active' => true,
                'version_valid_from' => $now,
                'version_valid_until' => null,
                'internal_comment' => self::TEST_MARKER,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $placeDetailId = DB::table('place_details')->insertGetId([
                'place_id' => $placeId,
                'operator_name' => 'Camperwolf Testbetrieb',
                'pitch_count' => 20,
                'is_active' => true,
                'version_valid_from' => $now,
                'version_valid_until' => null,
                'internal_comment' => self::TEST_MARKER,
                'created_by' => $testUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 1) Simple pending request.
            $this->insertRequest([
                'place_id' => $placeId,
                'suggestable_field_id' => $fields['places.name'],
                'target_record_id' => $placeId,
                'operation' => 'update',
                'original_value' => 'Testcamp am Beispielwald',
                'proposed_value' => 'Testcamp am Wolfswald',
                'status' => 'pending',
                'submitted_by' => $testUserId,
                'user_comment' => self::TEST_MARKER.' Der Platz hat einen neuen Namen.',
                'submitted_at' => $now->copy()->subMinutes(20),
            ]);

            // 2) Grouped pending address change. All three rows must be reviewed together.
            $groupUuid = (string) Str::uuid();
            foreach ([
                ['place_addresses.street', 'Beispielstraße', 'Waldweg'],
                ['place_addresses.house_number', '10', '42'],
                ['place_addresses.city', 'Essen', 'Testhausen'],
            ] as [$fieldKey, $oldValue, $newValue]) {
                $this->insertRequest([
                    'group_uuid' => $groupUuid,
                    'place_id' => $placeId,
                    'suggestable_field_id' => $fields[$fieldKey],
                    'target_record_id' => $addressId,
                    'operation' => 'update',
                    'original_value' => $oldValue,
                    'proposed_value' => $newValue,
                    'status' => 'pending',
                    'submitted_by' => $testUserId,
                    'user_comment' => self::TEST_MARKER.' Adresse wurde vor Ort geprüft.',
                    'submitted_at' => $now->copy()->subMinutes(15),
                ]);
            }

            // 3) Pending versioned place-details change.
            $this->insertRequest([
                'place_id' => $placeId,
                'suggestable_field_id' => $fields['place_details.pitch_count'],
                'target_record_id' => $placeDetailId,
                'operation' => 'update',
                'original_value' => 20,
                'proposed_value' => 25,
                'status' => 'pending',
                'submitted_by' => $testUserId,
                'user_comment' => self::TEST_MARKER.' Vor Ort wurden 25 nutzbare Stellplätze gezählt.',
                'submitted_at' => $now->copy()->subMinutes(10),
            ]);

            // 4) Already rejected example.
            $this->insertRequest([
                'place_id' => $placeId,
                'suggestable_field_id' => $fields['places.opening_status'],
                'target_record_id' => $placeId,
                'operation' => 'update',
                'original_value' => 'open',
                'proposed_value' => 'permanently_closed',
                'status' => 'rejected',
                'submitted_by' => $testUserId,
                'user_comment' => self::TEST_MARKER.' Angeblich dauerhaft geschlossen.',
                'reviewed_by' => null,
                'reviewed_at' => $now->copy()->subMinutes(5),
                'moderator_comment' => 'Testfall: Quelle reicht für eine dauerhafte Schließung nicht aus.',
                'submitted_at' => $now->copy()->subMinutes(30),
            ]);

            // 5) Approved but deliberately not applied yet.
            $this->insertRequest([
                'place_id' => $placeId,
                'suggestable_field_id' => $fields['places.legal_status'],
                'target_record_id' => $placeId,
                'operation' => 'update',
                'original_value' => 'unclear',
                'proposed_value' => 'overnight_allowed',
                'status' => 'approved',
                'submitted_by' => $testUserId,
                'user_comment' => self::TEST_MARKER.' Übernachtung laut Beschilderung erlaubt.',
                'reviewed_by' => null,
                'reviewed_at' => $now->copy()->subMinutes(3),
                'moderator_comment' => 'Testfall: genehmigt, aber noch nicht angewendet.',
                'submitted_at' => $now->copy()->subMinutes(25),
            ]);
        });

        $this->command?->info('Change request test data created. Remove it with: php artisan db:seed --class=RemoveChangeRequestTestDataSeeder');
    }

    private function insertRequest(array $values): void
    {
        $now = now();

        DB::table('change_requests')->insert([
            'group_uuid' => $values['group_uuid'] ?? null,
            'place_id' => $values['place_id'],
            'suggestable_field_id' => $values['suggestable_field_id'],
            'target_record_id' => $values['target_record_id'] ?? null,
            'operation' => $values['operation'],
            'original_value' => array_key_exists('original_value', $values)
                ? json_encode($values['original_value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'proposed_value' => array_key_exists('proposed_value', $values)
                ? json_encode($values['proposed_value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
            'status' => $values['status'] ?? 'pending',
            'submitted_by' => $values['submitted_by'] ?? null,
            'submitted_at' => $values['submitted_at'] ?? $now,
            'user_comment' => $values['user_comment'] ?? self::TEST_MARKER,
            'reviewed_by' => $values['reviewed_by'] ?? null,
            'reviewed_at' => $values['reviewed_at'] ?? null,
            'moderator_comment' => $values['moderator_comment'] ?? null,
            'result_record_id' => null,
            'applied_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('ChangeRequestTestSeeder may only run in local or testing environments.');
        }
    }
}
