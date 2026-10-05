<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RemoveChangeRequestTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->guardEnvironment();

        DB::transaction(function (): void {
            $placeId = DB::table('places')
                ->where('slug', ChangeRequestTestSeeder::TEST_PLACE_SLUG)
                ->value('id');

            $testUserId = DB::table('users')
                ->where('email', ChangeRequestTestSeeder::TEST_USER_EMAIL)
                ->value('id');

            $changeRequestIds = collect();
            if ($placeId) {
                $changeRequestIds = DB::table('change_requests')
                    ->where('place_id', $placeId)
                    ->pluck('id');
            }

            if ($changeRequestIds->isNotEmpty()) {
                // Includes moderation audit rows even when a real admin/owner reviewed the test requests.
                DB::table('audit_logs')
                    ->where('entity_type', 'change_request')
                    ->whereIn('entity_id', $changeRequestIds)
                    ->delete();

                // The Apply Service writes domain-level audit rows as the real reviewer.
                // Match those by the test change-request IDs before deleting the place itself.
                $domainEntityTypes = [
                    'place',
                    'place_address',
                    'place_translation',
                    'place_contact',
                    'place_detail',
                    'place_vehicle_type',
                    'place_feature',
                ];

                foreach ($changeRequestIds as $changeRequestId) {
                    DB::table('audit_logs')
                        ->whereIn('entity_type', $domainEntityTypes)
                        ->where('internal_comment', 'like', '%'.$changeRequestId.'%')
                        ->delete();
                }
            }

            if ($testUserId) {
                // Remove audits created by the dedicated test user and audits whose logical target is that test account.
                DB::table('audit_logs')
                    ->where(function ($query) use ($testUserId) {
                        $query->where('user_id', $testUserId)
                            ->orWhere(function ($targetQuery) use ($testUserId) {
                                $targetQuery->whereIn('entity_type', ['user_role', 'user_permission_override', 'user'])
                                    ->where('entity_id', $testUserId);
                            });
                    })
                    ->delete();
            }

            if ($placeId) {
                // Child rows, including change_requests and all versioned place records, are removed by FK cascades.
                DB::table('places')->where('id', $placeId)->delete();
            }

            if ($testUserId) {
                // Defensive cleanup for tables that may reference the test account without cascades.
                DB::table('sessions')->where('user_id', $testUserId)->delete();
                DB::table('user_roles')->where('user_id', $testUserId)->delete();
                DB::table('user_permission_overrides')->where('user_id', $testUserId)->delete();
                DB::table('user_settings')->where('user_id', $testUserId)->delete();
                DB::table('user_consents')->where('user_id', $testUserId)->delete();

                DB::table('users')->where('id', $testUserId)->delete();
            }
        });

        $this->command?->info('Change request test data removed completely.');
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('RemoveChangeRequestTestDataSeeder may only run in local or testing environments.');
        }
    }
}
