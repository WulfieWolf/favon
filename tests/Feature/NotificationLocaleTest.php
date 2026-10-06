<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserNotificationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorite_events_and_digests_use_each_recipient_locale(): void
    {
        $this->seed(DatabaseSeeder::class);

        $germanUser = User::factory()->create(['locale' => 'de']);
        $englishUser = User::factory()->create(['locale' => 'en']);
        $placeId = $this->createPlace($germanUser);

        foreach ([$germanUser, $englishUser] as $user) {
            DB::table('place_favorites')->insert([
                'user_id' => $user->id,
                'place_id' => $placeId,
                'notify_changes' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $notifications = app(UserNotificationService::class);
        $this->assertSame(2, $notifications->queueFavoritePlaceChange($placeId));

        $this->assertDatabaseHas('notification_events', [
            'user_id' => $germanUser->id,
            'title' => 'Favorit aktualisiert: Notification Testplatz',
        ]);
        $this->assertDatabaseHas('notification_events', [
            'user_id' => $englishUser->id,
            'title' => 'Favorite updated: Notification Testplatz',
        ]);

        DB::table('notification_events')->update(['occurred_at' => now()->subHour()]);
        $this->assertSame(2, $notifications->clusterDueEvents());

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $germanUser->id,
            'title' => 'Änderungen an Favoriten',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $englishUser->id,
            'title' => 'Changes to favorites',
        ]);
    }

    public function test_moderation_digest_uses_recipient_locale(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $notifications = app(UserNotificationService::class);

        $notifications->queueEvent(
            $user->id,
            'moderation_decision',
            'moderation:decisions',
            'Suggestion approved',
            'Your suggestion was approved.',
            payload: ['decision' => 'approved'],
        );

        DB::table('notification_events')->update(['occurred_at' => now()->subHour()]);
        $this->assertSame(1, $notifications->clusterDueEvents());

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'title' => 'Your suggestions have been reviewed',
            'message' => '1 suggestion was approved. Open the details to see each decision individually.',
        ]);
    }

    private function createPlace(User $user): int
    {
        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');

        if (! $placeTypeId) {
            $placeTypeId = DB::table('place_types')->insertGetId([
                'slug' => 'notification-test',
                'icon_id' => null,
                'sort_order' => 10,
                'is_active' => true,
                'is_searchable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Notification Testplatz',
            'slug' => 'notification-testplatz',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
