<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChangeRequestApplyDetailsTestSeeder extends Seeder
{
    public const TEST_MARKER = '[TEST:apply-service-details]';

    public function run(): void
    {
        $this->guardEnvironment();

        DB::transaction(function (): void {
            $place = DB::table('places')
                ->where('slug', ChangeRequestTestSeeder::TEST_PLACE_SLUG)
                ->first();

            if (! $place) {
                throw new RuntimeException('Change-request test place is missing. Run ChangeRequestTestSeeder first.');
            }

            $fieldId = DB::table('suggestable_fields')
                ->where('target_table', 'place_details')
                ->where('target_field', 'pitch_count')
                ->where('is_active', true)
                ->where('is_suggestable', true)
                ->value('id');

            if (! $fieldId) {
                throw new RuntimeException('Suggestable field place_details.pitch_count is missing or inactive.');
            }

            $testUserId = DB::table('users')
                ->where('email', ChangeRequestTestSeeder::TEST_USER_EMAIL)
                ->value('id');

            if (! $testUserId) {
                throw new RuntimeException('Change-request test user is missing. Run ChangeRequestTestSeeder first.');
            }

            $now = now();

            $details = DB::table('place_details')
                ->where('place_id', $place->id)
                ->where('is_active', true)
                ->whereNull('version_valid_until')
                ->orderByDesc('id')
                ->first();

            if (! $details) {
                $detailsId = DB::table('place_details')->insertGetId([
                    'place_id' => $place->id,
                    'operator_name' => 'Testcamp Betriebsgesellschaft',
                    'pitch_count' => 12,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => self::TEST_MARKER,
                    'created_by' => $testUserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $details = DB::table('place_details')->where('id', $detailsId)->first();
            }

            $oldPitchCount = $details->pitch_count === null ? 0 : (int) $details->pitch_count;
            $newPitchCount = $oldPitchCount + 6;

            $oldRequestIds = DB::table('change_requests')
                ->where('place_id', $place->id)
                ->where('user_comment', 'like', self::TEST_MARKER.'%')
                ->pluck('id');

            if ($oldRequestIds->isNotEmpty()) {
                DB::table('audit_logs')
                    ->where('entity_type', 'change_request')
                    ->whereIn('entity_id', $oldRequestIds)
                    ->delete();

                DB::table('change_requests')->whereIn('id', $oldRequestIds)->delete();
            }

            DB::table('change_requests')->insert([
                'group_uuid' => null,
                'place_id' => $place->id,
                'suggestable_field_id' => $fieldId,
                'target_record_id' => $details->id,
                'operation' => 'update',
                'original_value' => json_encode($oldPitchCount, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'proposed_value' => json_encode($newPitchCount, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'pending',
                'submitted_by' => $testUserId,
                'submitted_at' => $now,
                'user_comment' => self::TEST_MARKER.' Test der versionierten place_details-Anwendung.',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'moderator_comment' => null,
                'result_record_id' => null,
                'applied_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $this->command?->info('Pending place_details.pitch_count test request created.');
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('ChangeRequestApplyDetailsTestSeeder may only run in local or testing environments.');
        }
    }
}
