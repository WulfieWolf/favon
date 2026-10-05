<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemoDataResetService
{
    public function reset(): array
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Der Demo-Reset ist ausschließlich in local/testing erlaubt.');
        }

        $ownerEmail = config('camperwolf.owner_email');
        if (! is_string($ownerEmail) || trim($ownerEmail) === '') {
            throw new RuntimeException('CAMPERWOLF_OWNER_EMAIL ist nicht konfiguriert. Der Reset wurde abgebrochen.');
        }

        $owner = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [Str::lower(trim($ownerEmail))])
            ->first(['id', 'email', 'profile_photo_id']);

        if (! $owner) {
            throw new RuntimeException('Der konfigurierte Owner-Account wurde nicht gefunden. Der Reset wurde abgebrochen.');
        }

        $counts = [
            'places' => (int) DB::table('places')->count(),
            'non_owner_users' => (int) DB::table('users')->where('id', '!=', $owner->id)->count(),
            'reviews' => (int) DB::table('place_reviews')->count(),
        ];

        DB::transaction(function () use ($owner): void {
            // Keep the owner account itself usable, but clear its demo/community
            // state together with every other user's contribution state.
            DB::table('user_profiles')
                ->where('user_id', $owner->id)
                ->update([
                    'selected_badge_id' => null,
                    'updated_at' => now(),
                ]);

            // Gamification and community activity are user-generated.
            DB::table('user_badge_unlocks')->delete();
            DB::table('badge_progress_events')->delete();
            DB::table('user_activity_days')->delete();
            DB::table('xp_ledger')->delete();

            // Moderation/reporting state must not survive a fresh demo dataset.
            DB::table('place_review_reports')->delete();
            DB::table('audit_logs')->delete();
            DB::table('notification_events')->delete();
            DB::table('user_notification_reads')->delete();
            DB::table('user_notifications')->delete();
            DB::table('support_tickets')->delete();

            // These would also disappear through place cascades, but deleting
            // them explicitly makes the intent of the reset clear.
            DB::table('place_favorites')->delete();
            DB::table('change_requests')->delete();

            // Merge history deliberately restricts deletion of source/target
            // places so production merges cannot disappear accidentally.
            // A local demo reset is destructive by design, therefore remove
            // merge records (and cascading photo conflict rows) explicitly
            // before deleting the places themselves.
            DB::table('place_merges')->delete();

            // Deleting places cascades all place-owned versions, current and
            // legacy reviews, addresses, contacts, details, features, prices,
            // opening hours and source links.
            DB::table('places')->delete();

            // Notes are global rows linked to places and otherwise become
            // orphaned after the place reset.
            DB::table('notes')->delete();

            // Remove all test/demo accounts but preserve the configured owner.
            // Related settings, profiles, roles, consents and preferences use
            // cascading foreign keys and are removed with the user.
            DB::table('users')
                ->where('id', '!=', $owner->id)
                ->delete();

            // Profile photos are not owned by a place. Preserve only the photo
            // currently selected by the retained owner account, if any.
            $photos = DB::table('photos');
            if ($owner->profile_photo_id) {
                $photos->where('id', '!=', $owner->profile_photo_id);
            }
            $photos->delete();

            DB::table('password_reset_tokens')->delete();

            // Keep the current owner's browser session so running the reset
            // does not needlessly log the developer out.
            DB::table('sessions')
                ->where(function ($query) use ($owner): void {
                    $query->whereNull('user_id')
                        ->orWhere('user_id', '!=', $owner->id);
                })
                ->delete();
        });

        return $counts + [
            'owner_id' => (int) $owner->id,
            'owner_email' => $owner->email,
        ];
    }
}
