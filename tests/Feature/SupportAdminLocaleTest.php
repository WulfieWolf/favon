<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupportAdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_administration_is_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $reporter = User::factory()->create(['name' => 'Support Reporter']);
        config(['favon.owner_email' => $owner->email]);

        $ticketId = DB::table('support_tickets')->insertGetId([
            'user_id' => $reporter->id,
            'type' => 'bug',
            'status' => 'open',
            'priority' => 'high',
            'subject' => 'Original ticket subject',
            'description' => 'Original ticket description',
            'context_key' => 'place-profile',
            'module' => 'places',
            'route_name' => 'places.show',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('support_ticket_messages')->insert([
            'ticket_id' => $ticketId,
            'user_id' => $reporter->id,
            'message_type' => 'user_reply',
            'message' => 'Original user reply',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('support_ticket_events')->insert([
            'ticket_id' => $ticketId,
            'user_id' => $owner->id,
            'event_type' => 'status_changed',
            'old_value' => 'new',
            'new_value' => 'open',
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertSee('Meldungen von registrierten Nutzern und Gästen gemeinsam bearbeiten.')
            ->assertSee('Original ticket subject')
            ->assertSee('Hoch');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.support.index'))
            ->assertOk()
            ->assertSee('Process reports from registered users and guests in one place.')
            ->assertSee('All statuses')
            ->assertSee('High');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.support.show', $ticketId))
            ->assertOk()
            ->assertSee('Communication')
            ->assertSee('User reply')
            ->assertSee('Original user reply')
            ->assertSee('New')
            ->assertSee('Open');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.support.content'))
            ->assertOk()
            ->assertSee('Help &amp; public lists', false)
            ->assertSee('New help article')
            ->assertSee('Public description (EN)');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.support.articles.store'), [
                'context_key' => 'locale-test',
                'sort_order' => 100,
                'translations' => [
                    'de' => ['title' => 'Deutscher Admin-Hilfetitel', 'summary' => null, 'body' => 'Deutscher Admin-Hilfetext'],
                    'en' => ['title' => 'English admin help title', 'summary' => null, 'body' => 'English admin help text'],
                ],
            ])
            ->assertSessionHas('ui_toast', 'Help article created.');

        $this->assertDatabaseHas('support_article_translations', [
            'locale' => 'en',
            'title' => 'English admin help title',
            'body' => 'English admin help text',
        ]);

        $this->assertDatabaseHas('support_articles', [
            'title' => 'Deutscher Admin-Hilfetitel',
            'body' => 'Deutscher Admin-Hilfetext',
        ]);
    }
}
