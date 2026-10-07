<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemNotificationAdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_notification_form_and_feedback_are_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['favon.owner_email' => $owner->email]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.notifications.create'))
            ->assertOk()
            ->assertSee('Systemnachricht erstellen')
            ->assertSee('Die Nachricht erscheint bei allen aktuell registrierten Benutzern in der Glocke.')
            ->assertSee('Veröffentlichen');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.notifications.create'))
            ->assertOk()
            ->assertSee('Create system notification')
            ->assertSee('The notification appears in the notification menu of all currently registered users.')
            ->assertSee('Expiration date')
            ->assertSee('Publish');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.notifications.store'), [
                'title' => 'Original broadcast title',
                'message' => 'Original broadcast message',
                'priority' => 'important',
                'url' => 'https://example.test/announcement',
            ])
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('ui_toast', 'System notification published.');

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => null,
            'type' => 'system_announcement',
            'title' => 'Original broadcast title',
            'message' => 'Original broadcast message',
            'priority' => 'important',
        ]);
    }
}
