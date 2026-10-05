<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlaceReviewService;
use App\Services\ReviewModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PlaceReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_requires_one_current_version_and_calculates_score(): void
    {
        Carbon::setTestNow('2026-01-10 12:00:00');

        try {
            $user = User::factory()->create();
            $placeId = $this->createPlace($user->id);
            $service = app(PlaceReviewService::class);

            $result = $service->submit($user, $placeId, [
                'cleanliness' => 5,
                'functionality' => 4,
                'condition' => 3,
                'safety' => 4,
                'usability' => 5,
            ], 'Ein ausreichend langer Testtext für die Rezension.');

            $this->assertTrue($result['is_first']);
            $this->assertFalse($result['is_correction']);

            $this->assertDatabaseHas('place_review_versions', [
                'review_id' => $result['review_id'],
                'version_number' => 1,
                'overall_score' => 4.2,
                'rating_cleanliness' => 5,
                'rating_functionality' => 4,
                'rating_condition' => 3,
                'rating_safety' => 4,
                'rating_usability' => 5,
            ]);

            $summary = $service->summaryForPlace($placeId);
            $this->assertSame(1, $summary['count']);
            $this->assertSame(4.2, $summary['overall']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_first_thirty_minutes_are_a_correction_window_without_new_history_version(): void
    {
        Carbon::setTestNow('2026-01-10 12:00:00');

        try {
            $user = User::factory()->create();
            $placeId = $this->createPlace($user->id);
            $service = app(PlaceReviewService::class);

            $first = $service->submit($user, $placeId, $this->ratings(3), null);

            Carbon::setTestNow('2026-01-10 12:20:00');

            $corrected = $service->submit($user, $placeId, $this->ratings(5), 'Jetzt mit einem ausreichend langen Rezensionstext.');

            $this->assertTrue($corrected['is_correction']);
            $this->assertSame($first['version_id'], $corrected['version_id']);
            $this->assertSame(1, DB::table('place_review_versions')->where('review_id', $first['review_id'])->count());

            $this->assertDatabaseHas('place_review_versions', [
                'id' => $first['version_id'],
                'overall_score' => 5.0,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_review_cannot_be_versioned_again_before_twenty_eight_days(): void
    {
        Carbon::setTestNow('2026-01-10 12:00:00');

        try {
            $user = User::factory()->create();
            $placeId = $this->createPlace($user->id);
            $service = app(PlaceReviewService::class);

            $service->submit($user, $placeId, $this->ratings(3), null);

            Carbon::setTestNow('2026-01-11 12:00:00');

            $this->expectException(RuntimeException::class);
            $service->submit($user, $placeId, $this->ratings(4), null);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_new_version_after_twenty_eight_days_replaces_current_score_but_keeps_history(): void
    {
        Carbon::setTestNow('2026-01-01 12:00:00');

        try {
            $user = User::factory()->create();
            $placeId = $this->createPlace($user->id);
            $service = app(PlaceReviewService::class);

            $first = $service->submit($user, $placeId, $this->ratings(1), 'Der Platz war bei diesem Besuch wirklich nicht gut.');

            Carbon::setTestNow('2026-02-01 12:00:00');
            $second = $service->submit($user, $placeId, $this->ratings(5), 'Beim zweiten Besuch hatte sich sehr viel verbessert.');

            $this->assertFalse($second['is_correction']);
            $this->assertSame(2, DB::table('place_review_versions')->where('review_id', $first['review_id'])->count());

            $old = DB::table('place_review_versions')->where('id', $first['version_id'])->first();
            $this->assertSame('2026-02-01 12:00:00', Carbon::parse($old->valid_until)->format('Y-m-d H:i:s'));

            $summary = $service->summaryForPlace($placeId);
            $this->assertSame(5.0, $summary['overall']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_review_expires_after_twelve_months_without_deleting_history(): void
    {
        Carbon::setTestNow('2026-01-10 12:00:00');

        try {
            $user = User::factory()->create();
            $placeId = $this->createPlace($user->id);
            $service = app(PlaceReviewService::class);

            $service->submit($user, $placeId, $this->ratings(4), null);

            Carbon::setTestNow('2027-01-10 12:00:01');

            $summary = $service->summaryForPlace($placeId);
            $this->assertSame(0, $summary['count']);
            $this->assertNull($summary['overall']);
            $this->assertSame(1, DB::table('place_review_versions')->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_deleting_current_review_removes_it_from_current_score_but_preserves_past_period(): void
    {
        Carbon::setTestNow('2026-01-05 12:00:00');

        try {
            $user = User::factory()->create();
            $placeId = $this->createPlace($user->id);
            $service = app(PlaceReviewService::class);

            $service->submit($user, $placeId, $this->ratings(2), 'Diese Rezension wird später wieder entfernt werden.');

            Carbon::setTestNow('2026-03-15 12:00:00');
            $this->assertTrue($service->deleteCurrent($user, $placeId));
            $this->assertSame(0, $service->summaryForPlace($placeId)['count']);

            $history = collect($service->monthlyHistory($placeId, 3))->keyBy('month');
            $this->assertSame(2.0, $history['2026-01']['overall']);
            $this->assertSame(2.0, $history['2026-02']['overall']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_same_user_can_report_same_review_only_once_and_cannot_report_own_review(): void
    {
        $author = User::factory()->create();
        $reporter = User::factory()->create();
        $placeId = $this->createPlace($author->id);
        $service = app(PlaceReviewService::class);

        $result = $service->submit($author, $placeId, $this->ratings(4), null);

        $this->assertTrue($service->report($reporter, $result['review_id'], 'insult', 'Persönliche Beleidigung des Betreibers.'));
        $this->assertFalse($service->report($reporter, $result['review_id'], 'insult', null));
        $this->assertFalse($service->report($author, $result['review_id'], 'other', null));
    }

    public function test_dismissed_report_notifies_reporter_but_not_review_author(): void
    {
        $author = User::factory()->create();
        $reporter = User::factory()->create(['locale' => 'en']);
        $moderator = User::factory()->create();
        $placeId = $this->createPlace($author->id);

        $reviews = app(PlaceReviewService::class);
        $result = $reviews->submit($author, $placeId, $this->ratings(4), null);
        $this->assertTrue($reviews->report($reporter, $result['review_id'], 'misleading', 'Testmeldung'));

        $reportId = (int) DB::table('place_review_reports')->value('id');
        app(ReviewModerationService::class)->dismiss($moderator, $reportId, 'Kein Verstoß.');

        $this->assertDatabaseHas('place_review_reports', [
            'id' => $reportId,
            'status' => 'dismissed',
            'moderated_by' => $moderator->id,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $reporter->id,
            'type' => 'review_report_result',
            'title' => 'Reported review remains available',
        ]);

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $author->id,
            'type' => 'review_moderated',
        ]);
    }

    public function test_removed_review_stops_counting_and_notifies_reporter_and_author(): void
    {
        $author = User::factory()->create(['locale' => 'en']);
        $reporter = User::factory()->create();
        $moderator = User::factory()->create();
        $placeId = $this->createPlace($author->id);

        $reviews = app(PlaceReviewService::class);
        $result = $reviews->submit($author, $placeId, $this->ratings(2), 'Diese Testrezension wird durch Moderation entfernt.');
        $this->assertTrue($reviews->report($reporter, $result['review_id'], 'insult', 'Testmeldung'));

        $reportId = (int) DB::table('place_review_reports')->value('id');
        app(ReviewModerationService::class)->remove($moderator, $reportId, 'Verstoß bestätigt.');

        $this->assertSame(0, $reviews->summaryForPlace($placeId)['count']);

        $this->assertDatabaseHas('place_reviews', [
            'id' => $result['review_id'],
            'status' => 'moderated',
        ]);

        $this->assertDatabaseHas('place_review_versions', [
            'id' => $result['version_id'],
            'is_public' => false,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $reporter->id,
            'type' => 'review_report_result',
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $author->id,
            'type' => 'review_moderated',
            'title' => 'Your review was removed',
        ]);
    }

    public function test_service_rejects_incomplete_or_invalid_rating_sets(): void
    {
        $user = User::factory()->create();
        $placeId = $this->createPlace($user->id);
        $ratings = $this->ratings(4);
        unset($ratings['safety']);

        $this->expectException(InvalidArgumentException::class);

        app(PlaceReviewService::class)->submit($user, $placeId, $ratings, null);
    }

    public function test_hidden_current_version_is_neither_counted_nor_reportable(): void
    {
        $author = User::factory()->create();
        $reporter = User::factory()->create();
        $placeId = $this->createPlace($author->id);
        $service = app(PlaceReviewService::class);
        $result = $service->submit($author, $placeId, $this->ratings(4), null);

        DB::table('place_review_versions')
            ->where('id', $result['version_id'])
            ->update(['is_public' => false]);

        $this->assertSame(0, $service->summaryForPlace($placeId)['count']);
        $this->assertFalse($service->report($reporter, $result['review_id'], 'other', null));
    }

    public function test_rating_xp_uses_stable_dimension_keys(): void
    {
        $user = User::factory()->create();
        $placeId = $this->createPlace($user->id);

        app(PlaceReviewService::class)->submit($user, $placeId, $this->ratings(4), null);

        foreach (array_keys(PlaceReviewService::DIMENSIONS) as $dimension) {
            $this->assertDatabaseHas('xp_ledger', [
                'user_id' => $user->id,
                'dedupe_key' => "rating:{$user->id}:{$placeId}:{$dimension}",
                'action_key' => "rating:{$dimension}",
            ]);
        }
    }

    public function test_moderated_review_remains_in_past_monthly_scores(): void
    {
        Carbon::setTestNow('2026-01-05 12:00:00');

        try {
            $author = User::factory()->create();
            $reporter = User::factory()->create();
            $moderator = User::factory()->create();
            $placeId = $this->createPlace($author->id);
            $service = app(PlaceReviewService::class);

            $result = $service->submit($author, $placeId, $this->ratings(2), 'Diese Rezension wird später moderiert.');
            $this->assertTrue($service->report($reporter, $result['review_id'], 'insult', 'Testmeldung'));

            Carbon::setTestNow('2026-03-15 12:00:00');
            $reportId = (int) DB::table('place_review_reports')->value('id');
            app(ReviewModerationService::class)->remove($moderator, $reportId, 'Verstoß bestätigt.');

            $history = collect($service->monthlyHistory($placeId, 3))->keyBy('month');
            $this->assertSame(2.0, $history['2026-01']['overall']);
            $this->assertSame(2.0, $history['2026-02']['overall']);
            $this->assertSame(0, $service->summaryForPlace($placeId)['count']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_review_feed_returns_stable_five_item_cursor_batches(): void
    {
        Carbon::setTestNow('2026-04-01 12:00:00');

        try {
            $owner = User::factory()->create();
            $placeId = $this->createPlace($owner->id);
            $slug = (string) DB::table('places')->where('id', $placeId)->value('slug');
            $service = app(PlaceReviewService::class);
            $reviewIds = [];

            foreach (range(1, 11) as $offset) {
                Carbon::setTestNow(Carbon::parse('2026-04-01 12:00:00')->addMinutes($offset));
                $author = User::factory()->create();
                $reviewIds[] = $service->submit($author, $placeId, $this->ratings(4), null)['review_id'];
            }

            request()->query->remove('review_cursor');
            $firstPage = $service->reviewsForPlace($placeId, 'newest', 5);
            $this->assertSame(
                array_reverse(array_slice($reviewIds, -5)),
                $firstPage->getCollection()->pluck('review_id')->map(fn ($id) => (int) $id)->all(),
            );
            $this->assertTrue($firstPage->hasMorePages());

            $secondResponse = $this->getJson(route('places.reviews.feed', [
                'slug' => $slug,
                'sort' => 'newest',
                'review_cursor' => $firstPage->nextCursor()->encode(),
            ]));
            $secondResponse->assertOk()->assertJsonPath('count', 5);
            $secondHtml = (string) $secondResponse->json('html');
            foreach (array_reverse(array_slice($reviewIds, 1, 5)) as $reviewId) {
                $this->assertStringContainsString('data-review-id="'.$reviewId.'"', $secondHtml);
            }

            $thirdResponse = $this->getJson(route('places.reviews.feed', [
                'slug' => $slug,
                'sort' => 'newest',
                'review_cursor' => $secondResponse->json('next_cursor'),
            ]));
            $thirdResponse
                ->assertOk()
                ->assertJsonPath('count', 1)
                ->assertJsonPath('next_cursor', null);
            $this->assertStringContainsString('data-review-id="'.$reviewIds[0].'"', (string) $thirdResponse->json('html'));
        } finally {
            request()->query->remove('review_cursor');
            Carbon::setTestNow();
        }
    }

    private function ratings(int $value): array
    {
        return [
            'cleanliness' => $value,
            'functionality' => $value,
            'condition' => $value,
            'safety' => $value,
            'usability' => $value,
        ];
    }

    private function createPlace(int $userId): int
    {
        $placeTypeId = DB::table('place_types')->insertGetId([
            'slug' => 'review-test-'.uniqid(),
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
            'name' => 'Review Testplatz',
            'slug' => 'review-testplatz-'.uniqid(),
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
