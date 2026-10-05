<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlaceDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaceDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_permanent_deletion_keeps_only_tombstone_identity_and_preserves_gamification(): void
    {
        Storage::fake('local');

        $actor = User::factory()->create();
        $contributor = User::factory()->create();
        $typeId = $this->placeType('tombstone-campground');
        $placeId = $this->place($typeId, $contributor, 'Delete Me', 51.4500000, 7.0100000);

        DB::table('place_translations')->insert([
            'place_id' => $placeId,
            'locale' => 'de',
            'description' => 'Sensitive description',
            'directions' => 'Sensitive directions',
            'access_information' => null,
            'is_active' => true,
            'version_valid_from' => now(),
            'version_valid_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $uuid = (string) Str::uuid();
        $source = 'photo-uploads/'.$uuid.'.source';
        $detail = 'photos/'.$uuid.'/detail.webp';
        $preview = 'photos/'.$uuid.'/preview.webp';
        Storage::disk('local')->put($source, 'source');
        Storage::disk('local')->put($detail, 'detail');
        Storage::disk('local')->put($preview, 'preview');

        $photoId = DB::table('photos')->insertGetId([
            'uuid' => $uuid,
            'user_id' => $contributor->id,
            'storage_path' => $detail,
            'source_path' => $source,
            'preview_path' => $preview,
            'status' => 'approved',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_photos')->insert([
            'place_id' => $placeId,
            'photo_id' => $photoId,
            'photo_type' => 'community',
            'sort_order' => 10,
            'is_thumbnail_eligible' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $xpId = DB::table('xp_ledger')->insertGetId([
            'user_id' => $contributor->id,
            'event_type' => 'place_info',
            'source_type' => 'place',
            'source_id' => $placeId,
            'place_id' => $placeId,
            'action_key' => 'place_details.operator_name',
            'dedupe_key' => 'delete-test-xp-'.$placeId,
            'xp' => 2,
            'description' => 'Sensitive description for Delete Me',
            'rule_version' => 'v1',
            'created_at' => now(),
        ]);

        $badgeId = (int) DB::table('badge_definitions')->where('type', 'progress')->value('id');
        $progressId = DB::table('badge_progress_events')->insertGetId([
            'user_id' => $contributor->id,
            'badge_id' => $badgeId,
            'place_id' => $placeId,
            'contribution_key' => 'delete-test:'.$placeId,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $sourceId = DB::table('external_sources')->insertGetId([
            'slug' => 'delete-test-source',
            'name' => 'Delete test source',
            'source_type' => 'api',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $normalized = json_encode([
            'place' => [
                'name' => 'External Delete Me',
                'suggested_place_type' => 'tombstone-campground',
                'latitude' => 51.45,
                'longitude' => 7.01,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $normalized);

        $recordId = DB::table('external_records')->insertGetId([
            'external_source_id' => $sourceId,
            'external_id' => 'DELETE-1',
            'place_id' => $placeId,
            'status' => 'active',
            'normalized_hash' => $hash,
            'normalized_data' => $normalized,
            'classification' => 'created',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PlaceDeletionService::class)->delete(
            $actor,
            $placeId,
            'operator_request',
            'Requested through support.',
        );

        $place = DB::table('places')->where('id', $placeId)->first();

        $this->assertNotNull($place);
        $this->assertNull($place->name);
        $this->assertNull($place->slug);
        $this->assertSame($typeId, (int) $place->place_type_id);
        $this->assertEqualsWithDelta(51.45, (float) $place->latitude, 0.0000001);
        $this->assertEqualsWithDelta(7.01, (float) $place->longitude, 0.0000001);
        $this->assertFalse((bool) $place->is_active);
        $this->assertSame('deleted', $place->publication_status);
        $this->assertNotNull($place->deleted_at);
        $this->assertSame('operator_request', $place->deletion_reason);
        $this->assertSame('Requested through support.', $place->deletion_note);
        $this->assertNull($place->created_by);
        $this->assertDatabaseMissing('place_translations', ['place_id' => $placeId]);

        Storage::disk('local')->assertMissing($source);
        Storage::disk('local')->assertMissing($detail);
        Storage::disk('local')->assertMissing($preview);
        $this->assertDatabaseHas('photos', [
            'id' => $photoId,
            'status' => 'deleted',
            'is_active' => false,
            'storage_path' => 'deleted',
        ]);
        $this->assertDatabaseMissing('place_photos', ['place_id' => $placeId]);

        $this->assertDatabaseHas('xp_ledger', [
            'id' => $xpId,
            'user_id' => $contributor->id,
            'place_id' => null,
            'xp' => 2,
            'description' => '',
        ]);
        $this->assertDatabaseHas('badge_progress_events', [
            'id' => $progressId,
            'user_id' => $contributor->id,
            'place_id' => null,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('external_records', [
            'id' => $recordId,
            'place_id' => $placeId,
            'classification' => 'created',
            'tombstone_review_hash' => $hash,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'place_tombstone',
            'entity_id' => $placeId,
            'action' => 'place_permanently_deleted',
        ]);
    }

    private function placeType(string $slug): int
    {
        return (int) DB::table('place_types')->insertGetId([
            'slug' => $slug,
            'icon_id' => null,
            'sort_order' => 10,
            'is_active' => true,
            'is_searchable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function place(int $typeId, User $creator, string $name, float $lat, float $lon): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'latitude' => $lat,
            'longitude' => $lon,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'open',
            'is_active' => true,
            'created_by' => $creator->id,
            'approved_by' => $creator->id,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
