<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewReportAdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_report_moderation_is_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $author = User::factory()->create(['name' => 'Review Author']);
        $reporter = User::factory()->create(['name' => 'Report Author']);
        config(['camperwolf.owner_email' => $owner->email]);

        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => DB::table('place_types')->where('is_active', true)->value('id'),
            'name' => 'Review Locale Place',
            'slug' => 'review-locale-place',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reviewId = DB::table('place_reviews')->insertGetId([
            'place_id' => $placeId,
            'user_id' => $author->id,
            'status' => 'active',
            'current_published_at' => now(),
            'current_expires_at' => now()->addYear(),
            'verified_visit' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $versionId = DB::table('place_review_versions')->insertGetId([
            'review_id' => $reviewId,
            'version_number' => 1,
            'rating_cleanliness' => 4,
            'rating_functionality' => 4,
            'rating_condition' => 4,
            'rating_safety' => 4,
            'rating_usability' => 4,
            'overall_score' => 4.0,
            'review_text' => 'Original review text',
            'is_public' => true,
            'valid_from' => now(),
            'valid_until' => now()->addYear(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_reviews')->where('id', $reviewId)->update(['current_version_id' => $versionId]);

        $reportId = DB::table('place_review_reports')->insertGetId([
            'review_id' => $reviewId,
            'review_version_id' => $versionId,
            'reported_by' => $reporter->id,
            'reason' => 'misleading',
            'comment' => 'Original report comment',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.review-reports.index'))
            ->assertOk()
            ->assertSee('Gemeldete Rezensionen prüfen und moderieren.')
            ->assertSee('Falsch oder irreführend')
            ->assertSee('Original report comment');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.review-reports.index'))
            ->assertOk()
            ->assertSee('Review and moderate reported reviews.')
            ->assertSee('False or misleading')
            ->assertSee('Remove review')
            ->assertSee('Original review text');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.review-reports.dismiss', $reportId), [
                'moderator_comment' => 'Original moderator comment',
            ])
            ->assertSessionHas('ui_toast', 'The report was closed without removing the review.');

        $this->assertDatabaseHas('place_review_reports', [
            'id' => $reportId,
            'status' => 'dismissed',
            'moderator_comment' => 'Original moderator comment',
        ]);
    }
}
