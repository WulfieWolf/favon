<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\BadgeService;
use App\Services\PublicHandleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BadgeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_is_deduplicated_and_unlocks_bronze_once(): void
    {
        $user = User::factory()->create();
        $this->createProfile($user);
        $service = app(BadgeService::class);

        $this->assertTrue($service->recordProgress($user->id, 'explorer', 'place:1'));
        $this->assertFalse($service->recordProgress($user->id, 'explorer', 'place:1'));

        for ($place = 2; $place <= 5; $place++) {
            $service->recordProgress($user->id, 'explorer', 'place:'.$place);
        }

        $badgeId = DB::table('badge_definitions')->where('slug', 'explorer')->value('id');

        $this->assertSame(5, DB::table('badge_progress_events')
            ->where('user_id', $user->id)
            ->where('badge_id', $badgeId)
            ->where('is_active', true)
            ->count());

        $this->assertDatabaseHas('user_badge_unlocks', [
            'user_id' => $user->id,
            'badge_id' => $badgeId,
            'tier' => 'bronze',
            'revoked_at' => null,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'badge_unlocked',
        ]);
    }

    public function test_photo_progress_is_capped_at_five_per_place_and_decreases_on_delete(): void
    {
        $user = User::factory()->create();
        $this->createProfile($user);
        $placeId = $this->createPlace($user->id);
        $service = app(BadgeService::class);

        for ($photo = 1; $photo <= 6; $photo++) {
            $service->recordPhotoAccepted($user->id, $placeId, $photo);
        }

        $badgeId = DB::table('badge_definitions')->where('slug', 'photographer')->value('id');

        $this->assertSame(5, DB::table('badge_progress_events')
            ->where('user_id', $user->id)
            ->where('badge_id', $badgeId)
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->count());

        $this->assertTrue($service->recordPhotoDeleted($user->id, 1));

        $this->assertSame(4, DB::table('badge_progress_events')
            ->where('user_id', $user->id)
            ->where('badge_id', $badgeId)
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->count());

        $this->assertTrue($service->recordPhotoAccepted($user->id, $placeId, 7));

        $this->assertSame(5, DB::table('badge_progress_events')
            ->where('user_id', $user->id)
            ->where('badge_id', $badgeId)
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->count());
    }

    public function test_manual_badge_awards_fixed_xp_comment_and_can_be_selected_as_title(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();
        $profile = $this->createProfile($user);
        $service = app(BadgeService::class);

        $badge = DB::table('badge_definitions')->where('slug', 'wulfie-getroffen')->first();

        $this->assertTrue($service->grantManualBadge(
            $user,
            (int) $badge->id,
            'Beim Stellplatz persönlich Hallo gesagt.',
            $admin,
        ));

        $this->assertDatabaseHas('user_badge_unlocks', [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
            'award_comment' => 'Beim Stellplatz persönlich Hallo gesagt.',
            'revoked_at' => null,
        ]);

        $this->assertDatabaseHas('xp_ledger', [
            'user_id' => $user->id,
            'event_type' => 'manual_badge',
            'source_id' => $badge->id,
            'xp' => 25,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'badge_unlocked',
        ]);

        $profile->update(['selected_badge_id' => $badge->id]);

        $this->assertSame('Wulfie getroffen', $service->titleForUser($user->id)['label']);
    }

    public function test_badge_tier_labels_follow_current_locale(): void
    {
        $user = User::factory()->create();
        $this->createProfile($user);
        $service = app(BadgeService::class);

        for ($place = 1; $place <= 25; $place++) {
            $service->recordProgress($user->id, 'explorer', 'locale-place:'.$place);
        }

        app()->setLocale('de');
        $de = collect($service->profileSummary($user->id, true)['progress_badges'])->firstWhere('slug', 'explorer');
        $this->assertSame('Silber', $de['tier_label']);

        app()->setLocale('en');
        $en = collect($service->profileSummary($user->id, true)['progress_badges'])->firstWhere('slug', 'explorer');
        $this->assertSame('Silver', $en['tier_label']);
    }

    private function createProfile(User $user): UserProfile
    {
        return UserProfile::create([
            'user_id' => $user->id,
            'public_handle' => app(PublicHandleService::class)->automaticForUserId((int) $user->id),
        ]);
    }

    private function createPlace(int $userId): int
    {
        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'badge-test-'.uniqid(),
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'internal_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Badge Testplatz',
            'slug' => 'badge-testplatz-'.uniqid(),
            'latitude' => 51.0,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
