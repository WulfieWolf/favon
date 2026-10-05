<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PhotoMergeConflictService;
use App\Services\PlaceMergeService;
use App\Services\PlaceReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaceMergeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparison_suggests_known_values_and_deletes_real_conflicts(): void
    {
        $user = User::factory()->create();
        $type = $this->placeType();
        $main = $this->place($type, $user, 'Main', '51.0000', 'unclear', 'allowed');
        $duplicate = $this->place($type, $user, 'Duplicate', '51.1000', 'open', 'prohibited');

        $comparison = app(PlaceMergeService::class)->comparison($main, $duplicate);

        $this->assertSame('main', $comparison['fields']['latitude']['suggested']);
        $this->assertSame('duplicate', $comparison['fields']['opening_status']['suggested']);
        $this->assertSame('delete', $comparison['fields']['legal_status']['suggested']);
        $this->assertSame('main', $comparison['fields']['name']['suggested']);
    }

    public function test_merge_archives_older_duplicate_review_and_creates_photo_selection_conflict(): void
    {
        $actor = User::factory()->create();
        $author = User::factory()->create();
        $type = $this->placeType();
        $main = $this->place($type, $author, 'Main', '51.0', 'open');
        $duplicate = $this->place($type, $author, 'Duplicate', '51.1', 'open');

        Carbon::setTestNow('2026-01-01 12:00:00');
        $mainReview = app(PlaceReviewService::class)->submit($author, $main, $this->ratings(3), 'Main review');
        Carbon::setTestNow('2026-02-02 12:00:00');
        $duplicateReview = app(PlaceReviewService::class)->submit($author, $duplicate, $this->ratings(5), 'Newer duplicate review');
        foreach (range(1, 3) as $index) {
            $this->photo($author, $main, $mainReview['review_id'], $index);
            $this->photo($author, $duplicate, $duplicateReview['review_id'], $index + 3);
        }

        $service = app(PlaceMergeService::class);
        $comparison = $service->comparison($main, $duplicate);
        $fieldChoices = collect($comparison['fields'])->mapWithKeys(fn ($field, $key) => [$key => $field['suggested']])->all();
        $recordChoices = collect($comparison['records'])->mapWithKeys(fn ($record) => [$record['token'] => $record['suggested']])->all();
        $service->merge($actor, $main, $duplicate, $fieldChoices, $recordChoices);

        Carbon::setTestNow();
        $this->assertDatabaseHas('places', ['id' => $duplicate, 'is_active' => false]);
        $this->assertDatabaseHas('place_merges', ['source_place_id' => $duplicate, 'target_place_id' => $main, 'status' => 'completed']);
        $this->assertDatabaseHas('place_reviews', ['id' => $duplicateReview['review_id'], 'status' => 'merged_archived']);
        $this->assertSame(6, DB::table('place_photos')->where('place_id', $main)->where('place_review_id', $mainReview['review_id'])->count());
        $this->assertDatabaseHas('photo_merge_conflicts', ['place_id' => $main, 'user_id' => $author->id, 'status' => 'selection_required']);
        $this->assertTrue(app(PhotoMergeConflictService::class)->forUser($author)->first()->remaining_to_remove === 1);

        $originalLocale = app()->getLocale();

        try {
            foreach ([
                'de' => 'Bitte löse zuerst die offene Fotoauswahl für diesen Platz.',
                'en' => 'Please resolve the pending photo selection for this place first.',
            ] as $locale => $message) {
                app()->setLocale($locale);

                try {
                    app(PhotoMergeConflictService::class)->assertUploadsAllowed($author->id, $main);
                    $this->fail('Expected the pending photo selection to block another upload.');
                } catch (\RuntimeException $exception) {
                    $this->assertSame($message, $exception->getMessage());
                }
            }
        } finally {
            app()->setLocale($originalLocale);
        }

        $photoId = (int) DB::table('place_photos')->where('place_id', $main)->value('photo_id');
        DB::table('photos')->where('id', $photoId)->update(['status' => 'removed', 'is_active' => false]);
        DB::table('place_photos')->where('photo_id', $photoId)->update(['is_active' => false]);
        app(PhotoMergeConflictService::class)->resolveEligibleForPhoto($photoId);
        $this->assertDatabaseHas('photo_merge_conflicts', ['place_id' => $main, 'user_id' => $author->id, 'status' => 'resolved']);
    }

    public function test_merge_moves_external_record_links_to_main_place_and_reverse_restores_them(): void
    {
        $actor = User::factory()->create();
        $creator = User::factory()->create();
        $type = $this->placeType();

        $main = $this->place($type, $creator, 'Main External Link', '51.0', 'open');
        $duplicate = $this->place($type, $creator, 'Duplicate External Link', '51.1', 'open');

        $sourceId = (int) DB::table('external_sources')->insertGetId([
            'slug' => 'merge-external-link-test',
            'name' => 'Merge External Link Test',
            'source_type' => 'test',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mainRecord = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'MAIN-REC',
            'place_id' => $main,
            'status' => 'active',
            'classification' => 'linked',
            'classified_at' => now(),
            'normalized_data' => json_encode(['place' => ['name' => 'Main External Link']]),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $duplicateRecord = (int) DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'DUP-REC',
            'place_id' => $duplicate,
            'status' => 'active',
            'classification' => 'created',
            'classified_at' => now(),
            'normalized_data' => json_encode(['place' => ['name' => 'Duplicate External Link']]),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(PlaceMergeService::class);
        $comparison = $service->comparison($main, $duplicate);
        $fieldChoices = collect($comparison['fields'])->mapWithKeys(fn ($field, $key) => [$key => $field['suggested']])->all();
        $recordChoices = collect($comparison['records'])->mapWithKeys(fn ($record) => [$record['token'] => $record['suggested']])->all();

        $mergeId = $service->merge($actor, $main, $duplicate, $fieldChoices, $recordChoices);

        $this->assertSame($main, (int) DB::table('external_records')->where('id', $mainRecord)->value('place_id'));
        $this->assertSame($main, (int) DB::table('external_records')->where('id', $duplicateRecord)->value('place_id'));

        $service->reverse($actor, $mergeId);

        $this->assertSame($main, (int) DB::table('external_records')->where('id', $mainRecord)->value('place_id'));
        $this->assertSame($duplicate, (int) DB::table('external_records')->where('id', $duplicateRecord)->value('place_id'));
    }

    private function placeType(): int
    {
        return (int) DB::table('place_types')->insertGetId([
            'slug' => 'merge-test-'.Str::lower(Str::random(8)), 'icon_id' => null, 'sort_order' => 10,
            'is_active' => true, 'is_searchable' => true, 'internal_comment' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function place(int $type, User $creator, string $name, string $latitude, string $openingStatus, string $legalStatus = 'unclear'): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $type, 'name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'latitude' => $latitude, 'longitude' => 7.0, 'publication_status' => 'published',
            'legal_status' => $legalStatus, 'opening_status' => $openingStatus, 'is_active' => true,
            'created_by' => $creator->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ratings(int $value): array
    {
        return array_fill_keys(['cleanliness', 'functionality', 'condition', 'safety', 'usability'], $value);
    }

    private function photo(User $author, int $placeId, int $reviewId, int $sort): int
    {
        $uuid = (string) Str::uuid();
        $photoId = (int) DB::table('photos')->insertGetId([
            'uuid' => $uuid, 'user_id' => $author->id, 'storage_path' => 'photos/'.$uuid.'/detail.webp',
            'source_path' => null, 'preview_path' => 'photos/'.$uuid.'/preview.webp', 'original_filename' => null,
            'mime_type' => 'image/webp', 'file_size' => 1000, 'preview_file_size' => 500,
            'width' => 1600, 'height' => 1200, 'status' => 'approved', 'moderated_by' => null,
            'moderated_at' => null, 'moderation_reason' => null, 'processing_error' => null,
            'is_active' => true, 'internal_comment' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('place_photos')->insert([
            'place_id' => $placeId, 'place_review_id' => $reviewId, 'photo_id' => $photoId,
            'photo_type' => 'review', 'sort_order' => $sort * 10, 'is_thumbnail_eligible' => true,
            'thumbnail_excluded_by' => null, 'thumbnail_excluded_at' => null, 'thumbnail_exclusion_reason' => null,
            'is_active' => true, 'internal_comment' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $photoId;
    }
}
