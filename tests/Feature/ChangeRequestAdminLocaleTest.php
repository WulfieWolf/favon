<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChangeRequestAdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_request_queue_and_detail_are_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $submitter = User::factory()->create(['name' => 'Locale Submitter']);
        config(['camperwolf.owner_email' => $owner->email]);

        $placeId = DB::table('places')->insertGetId([
            'place_type_id' => DB::table('place_types')->where('is_active', true)->value('id'),
            'name' => 'Locale Test Place',
            'slug' => 'locale-test-place',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $submitter->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $changeRequestId = DB::table('change_requests')->insertGetId([
            'place_id' => $placeId,
            'suggestable_field_id' => DB::table('suggestable_fields')
                ->where('target_table', 'places')
                ->where('target_field', 'name')
                ->value('id'),
            'target_record_id' => $placeId,
            'operation' => 'update',
            'original_value' => json_encode('Alter Name'),
            'proposed_value' => json_encode('New name'),
            'status' => 'pending',
            'submitted_by' => $submitter->id,
            'submitted_at' => now(),
            'user_comment' => 'Original user comment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.change-requests.index'))
            ->assertOk()
            ->assertSee('Moderationswarteschlange für vorgeschlagene Änderungen an Platzdaten.')
            ->assertSee('Locale Submitter')
            ->assertSee('Ändern');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.change-requests.index'))
            ->assertOk()
            ->assertSee('Moderation queue for suggested changes to place data.')
            ->assertSee('Locale Submitter')
            ->assertSee('Update');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.change-requests.show', $changeRequestId))
            ->assertOk()
            ->assertSee('Review change suggestion')
            ->assertSee('Suggested changes')
            ->assertSee('Approval comment')
            ->assertSee('Original user comment');
    }
}
