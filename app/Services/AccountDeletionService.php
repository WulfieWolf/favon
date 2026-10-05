<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AccountDeletionService
{
    public const GRACE_DAYS = 30;

    public function statistics(int $userId): array
    {
        return [
            'reviews' => Schema::hasTable('place_reviews')
                ? DB::table('place_reviews')->where('user_id', $userId)->where('status', 'active')->count()
                : 0,
            'places' => Schema::hasTable('places')
                ? DB::table('places')->where('created_by', $userId)->count()
                : 0,
            'changes' => Schema::hasTable('change_requests')
                ? DB::table('change_requests')->where('submitted_by', $userId)->count()
                : 0,
        ];
    }

    public function request(User $user, bool $immediate): void
    {
        if (($user->account_status ?? 'active') === 'deleted') {
            throw new RuntimeException('Account is already deleted.');
        }

        if ($immediate) {
            $this->finalize((int) $user->id);

            return;
        }

        DB::table('users')->where('id', $user->id)->update([
            'account_status' => 'pending_deletion',
            'deletion_requested_at' => now(),
            'deletion_scheduled_for' => now()->addDays(self::GRACE_DAYS),
            'updated_at' => now(),
        ]);

        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    public function cancel(User $user): bool
    {
        if (($user->account_status ?? 'active') !== 'pending_deletion') {
            return false;
        }

        return DB::table('users')->where('id', $user->id)->where('account_status', 'pending_deletion')->update([
            'account_status' => 'active',
            'deletion_requested_at' => null,
            'deletion_scheduled_for' => null,
            'updated_at' => now(),
        ]) > 0;
    }

    public function finalizeDue(): int
    {
        $ids = DB::table('users')
            ->where('account_status', 'pending_deletion')
            ->whereNotNull('deletion_scheduled_for')
            ->where('deletion_scheduled_for', '<=', now())
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $id) {
            $this->finalize((int) $id);
        }

        return $ids->count();
    }

    public function finalize(int $userId): void
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (! $user || ($user->account_status ?? 'active') === 'deleted') {
            return;
        }

        $oldEmail = (string) $user->email;
        $now = now();

        $this->deleteDataExports($userId);
        $this->retireReviews($userId, $now);

        DB::transaction(function () use ($userId, $oldEmail, $now): void {
            $this->deletePersonalRows($userId);
            $this->detachSupport($userId);
            $this->detachTechnicalLogs($userId);
            $this->anonymizeReferences($userId);
            $this->scrubProfile($userId, $now);

            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();
            }

            DB::table('sessions')->where('user_id', $userId)->delete();

            $values = [
                'name' => 'Deleted User',
                'email' => 'deleted-'.$userId.'-'.Str::lower(Str::random(24)).'@deleted.invalid',
                'email_verified_at' => null,
                'password' => Hash::make(Str::random(96)),
                'remember_token' => null,
                'last_seen_at' => null,
                'account_status' => 'deleted',
                'suspension_reason' => null,
                'suspended_until' => null,
                'deletion_requested_at' => DB::raw('COALESCE(deletion_requested_at, CURRENT_TIMESTAMP)'),
                'deletion_scheduled_for' => null,
                'deleted_at' => $now,
                'updated_at' => $now,
            ];

            foreach (['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $values[$column] = null;
                }
            }

            DB::table('users')->where('id', $userId)->update($values);
        });
    }


    private function deleteDataExports(int $userId): void
    {
        if (! Schema::hasTable('data_export_requests')) {
            return;
        }

        $exports = DB::table('data_export_requests')
            ->where('user_id', $userId)
            ->get(['storage_path']);

        foreach ($exports as $export) {
            if ($export->storage_path) {
                Storage::disk('local')->delete($export->storage_path);
            }
        }

        DB::table('data_export_requests')->where('user_id', $userId)->delete();
    }

    private function retireReviews(int $userId, $now): void
    {
        if (! Schema::hasTable('place_reviews') || ! Schema::hasTable('place_review_versions')) {
            return;
        }

        $reviews = DB::table('place_reviews')->where('user_id', $userId)->get(['id', 'current_version_id']);

        foreach ($reviews as $review) {
            DB::table('place_review_versions')->where('review_id', $review->id)->update([
                'review_text' => null,
                'updated_at' => $now,
            ]);

            if ($review->current_version_id) {
                $version = DB::table('place_review_versions')->where('id', $review->current_version_id)->first(['valid_until']);
                $updates = ['is_public' => false, 'updated_at' => $now];

                if ($version && $version->valid_until && \Illuminate\Support\Carbon::parse($version->valid_until)->gt($now)) {
                    $updates['valid_until'] = $now;
                }

                DB::table('place_review_versions')->where('id', $review->current_version_id)->update($updates);
            }

            DB::table('place_reviews')->where('id', $review->id)->update([
                'status' => 'deleted',
                'current_expires_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function deletePersonalRows(int $userId): void
    {
        $tables = [
            'place_favorites',
            'user_profile_social_links',
            'user_settings',
            'user_consents',
            'user_notification_reads',
            'notification_events',
            'user_notifications',
            'xp_ledger',
            'badge_progress_events',
            'user_badge_unlocks',
            'user_activity_days',
            'user_roles',
            'user_permission_overrides',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $userId)->delete();
            }
        }

        foreach (['passkeys', 'webauthn_credentials'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $userId)->delete();
            }
        }
    }

    private function detachSupport(int $userId): void
    {
        foreach (['support_tickets', 'support_ticket_messages', 'support_ticket_events'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $userId)->update(['user_id' => null]);
            }
        }

        if (Schema::hasTable('support_tickets') && Schema::hasColumn('support_tickets', 'assigned_to')) {
            DB::table('support_tickets')->where('assigned_to', $userId)->update(['assigned_to' => null]);
        }
    }

    private function detachTechnicalLogs(int $userId): void
    {
        foreach (['security_events', 'abuse_flags'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $userId)->update(['user_id' => null]);
            }
        }
    }

    private function anonymizeReferences(int $userId): void
    {
        $nullableReferences = [
            ['audit_logs', 'user_id'],
            ['change_requests', 'reviewed_by'],
            ['places', 'approved_by'],
            ['user_notifications', 'created_by'],
            ['xp_ledger', 'awarded_by'],
            ['user_badge_unlocks', 'awarded_by'],
            ['user_roles', 'assigned_by'],
            ['user_permission_overrides', 'set_by'],
            ['abuse_flags', 'resolved_by'],
        ];

        foreach ($nullableReferences as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $userId)->update([$column => null]);
            }
        }

        // Deliberately retained: places.created_by, change_requests.submitted_by,
        // place_reviews.user_id remains linked to the anonymous tombstone account
        // so moderation and contribution history can remain internally consistent.
    }

    private function scrubProfile(int $userId, $now): void
    {
        if (! Schema::hasTable('user_profiles')) {
            return;
        }

        DB::table('user_profiles')->where('user_id', $userId)->update([
            'public_handle' => 'deleted-'.Str::lower((string) Str::uuid()),
            'public_alias' => null,
            'alias_finalized_at' => null,
            'handle_finalized_at' => null,
            'selected_badge_id' => null,
            'bio' => null,
            'hometown_city' => null,
            'hometown_country_code' => null,
            'hometown_latitude' => null,
            'hometown_longitude' => null,
            'hometown_source_id' => null,
            'birth_date' => null,
            'gender' => null,
            'gender_custom' => null,
            'vehicle_type' => null,
            'vehicle_details' => null,
            'updated_at' => $now,
        ]);
    }
}
