<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResearchReviewApplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_apply_research_review_with_versioned_address_and_history(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $typeId = (int) DB::table('place_types')->insertGetId([
            'slug' => 'research-apply-test',
            'is_active' => true,
            'is_searchable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => 'Alter Name',
            'slug' => 'alter-name',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'open',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldAddressId = (int) DB::table('place_addresses')->insertGetId([
            'place_id' => $placeId,
            'country_code' => 'DE',
            'region_id' => null,
            'postal_code' => '45127',
            'city' => 'Essen',
            'street' => 'Altstraße',
            'house_number' => '9',
            'address_addition' => null,
            'is_active' => true,
            'version_valid_from' => now()->subDay(),
            'version_valid_until' => null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $csv = implode(';', [
            'place_id',
            'author',
            'source_label',
            'source_url',
            'researched_at',
            'notes',
            'name',
            'house_number',
        ])."\n".implode(';', [
            $placeId,
            'Wulfie',
            'Google Maps',
            'https://maps.google.com/',
            '2026-10-01 07:30:00',
            'Test',
            'Neuer Name',
            '69',
        ])."\n";

        $this->actingAs($owner)
            ->post(route('admin.imports.research-upload'), [
                'research_file' => UploadedFile::fake()->createWithContent('research.csv', $csv),
            ])
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasNoErrors();

        $reviewId = (int) DB::table('external_import_review_items')
            ->where('place_id', $placeId)
            ->where('type', 'research_update')
            ->value('id');

        $this->assertGreaterThan(0, $reviewId);

        $this->actingAs($owner)
            ->post(route('admin.imports.research-reviews.approve', $reviewId))
            ->assertRedirect(route('places.show', 'alter-name'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Neuer Name', DB::table('places')->where('id', $placeId)->value('name'));

        $oldAddress = DB::table('place_addresses')->where('id', $oldAddressId)->first();
        $this->assertFalse((bool) $oldAddress->is_active);
        $this->assertNotNull($oldAddress->version_valid_until);

        $newAddress = DB::table('place_addresses')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first();

        $this->assertNotNull($newAddress);
        $this->assertSame('69', $newAddress->house_number);
        $this->assertSame('Altstraße', $newAddress->street);

        $this->assertDatabaseHas('external_import_review_items', [
            'id' => $reviewId,
            'status' => 'resolved',
            'resolved_by' => $owner->id,
        ]);

        $history = DB::table('place_history')
            ->where('place_id', $placeId)
            ->where('action', 'research_data_applied')
            ->first();

        $this->assertNotNull($history);
        $metadata = json_decode((string) $history->metadata, true);
        $this->assertSame('Wulfie', $metadata['author']);
        $this->assertSame('Google Maps', $metadata['source_label']);
        $this->assertSame('https://maps.google.com/', $metadata['source_url']);
        $this->assertContains([
            'field' => 'name',
            'old' => 'Alter Name',
            'new' => 'Neuer Name',
        ], $metadata['research_changes']);
        $this->assertContains([
            'field' => 'house_number',
            'old' => '9',
            'new' => '69',
        ], $metadata['research_changes']);
    }

    public function test_admin_can_bulk_apply_all_unambiguous_research_reviews(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $typeId = (int) DB::table('place_types')->insertGetId([
            'slug' => 'research-bulk-test',
            'is_active' => true,
            'is_searchable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeIds = [];
        foreach (['Bulk Alt A', 'Bulk Alt B'] as $index => $name) {
            $placeIds[] = (int) DB::table('places')->insertGetId([
                'place_type_id' => $typeId,
                'name' => $name,
                'slug' => 'bulk-alt-'.($index + 1),
                'latitude' => 51.45 + ($index / 100),
                'longitude' => 7.01 + ($index / 100),
                'publication_status' => 'published',
                'legal_status' => 'unclear',
                'opening_status' => 'open',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $csv = "place_id;author;source_label;source_url;researched_at;notes;name\n"
            .$placeIds[0].";Wulfie;Maps;https://maps.google.com/;;;Bulk Neu A\n"
            .$placeIds[1].";Wulfie;Maps;https://maps.google.com/;;;Bulk Neu B\n";

        $this->actingAs($owner)
            ->post(route('admin.imports.research-upload'), [
                'research_file' => UploadedFile::fake()->createWithContent('research.csv', $csv),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            2,
            DB::table('external_import_review_items')
                ->where('status', 'pending')
                ->where('type', 'research_update')
                ->count()
        );

        $this->actingAs($owner)
            ->post(route('admin.imports.research-reviews.approve-all'))
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Bulk Neu A', DB::table('places')->where('id', $placeIds[0])->value('name'));
        $this->assertSame('Bulk Neu B', DB::table('places')->where('id', $placeIds[1])->value('name'));

        $this->assertSame(
            0,
            DB::table('external_import_review_items')
                ->where('status', 'pending')
                ->where('type', 'research_update')
                ->count()
        );
    }

    public function test_research_apply_aborts_when_current_value_changed_after_upload(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $typeId = (int) DB::table('place_types')->insertGetId([
            'slug' => 'research-stale-test',
            'is_active' => true,
            'is_searchable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => 'Original',
            'slug' => 'original',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'open',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $csv = "place_id;author;source_label;source_url;researched_at;notes;name\n"
            .$placeId.";Wulfie;Maps;https://maps.google.com/;;;Recherche-Name\n";

        $this->actingAs($owner)
            ->post(route('admin.imports.research-upload'), [
                'research_file' => UploadedFile::fake()->createWithContent('research.csv', $csv),
            ])
            ->assertSessionHasNoErrors();

        $reviewId = (int) DB::table('external_import_review_items')
            ->where('place_id', $placeId)
            ->where('status', 'pending')
            ->value('id');

        DB::table('places')->where('id', $placeId)->update(['name' => 'Zwischenzeitlich geändert']);

        $this->actingAs($owner)
            ->post(route('admin.imports.research-reviews.approve', $reviewId))
            ->assertSessionHasErrors('research_review');

        $this->assertSame('Zwischenzeitlich geändert', DB::table('places')->where('id', $placeId)->value('name'));
        $this->assertDatabaseHas('external_import_review_items', [
            'id' => $reviewId,
            'status' => 'pending',
        ]);
    }
}
