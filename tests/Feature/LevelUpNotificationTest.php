<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LevelService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LevelUpNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_up_notification_respects_gamification_and_collapses_rapid_multiple_level_ups(): void
    {
        $levels = app(LevelService::class);
        $xp = app(XpService::class);

        $user = User::factory()->create(['locale' => 'de']);
        DB::table('user_settings')->insert([
            'user_id' => $user->id,
            'show_gamification' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $levelTwoXp = $levels->thresholdForLevel(2);
        $levelThreeXp = $levels->thresholdForLevel(3);

        $this->assertTrue($xp->awardUnique(
            $user->id,
            'test_level',
            'test-level-2',
            $levelTwoXp,
            'Test Level 2',
        ));

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'type' => 'level_up',
            'title' => 'Level 2 erreicht',
        ]);

        $this->assertTrue($xp->awardUnique(
            $user->id,
            'test_level',
            'test-level-3',
            $levelThreeXp - $levelTwoXp,
            'Test Level 3',
        ));

        $notifications = DB::table('user_notifications')
            ->where('user_id', $user->id)
            ->where('type', 'level_up')
            ->get();

        $this->assertCount(1, $notifications);
        $this->assertSame('Level 3 erreicht', $notifications->first()->title);
        $this->assertStringStartsWith('Du bist jetzt Level 3 - ', $notifications->first()->message);

        $hiddenUser = User::factory()->create(['locale' => 'de']);
        DB::table('user_settings')->insert([
            'user_id' => $hiddenUser->id,
            'show_gamification' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($xp->awardUnique(
            $hiddenUser->id,
            'test_level',
            'hidden-test-level',
            $levelTwoXp,
            'Hidden test level',
        ));

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $hiddenUser->id,
            'type' => 'level_up',
        ]);
    }
}
