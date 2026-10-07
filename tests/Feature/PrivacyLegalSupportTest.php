<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrivacyLegalSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_privacy_and_legal_entry_is_public_and_separate_from_regular_support(): void
    {
        $this->withSession(['locale' => 'de'])
            ->get(route('support.privacy-legal'))
            ->assertOk()
            ->assertSee('Datenschutz &amp; rechtliche Anfragen', false)
            ->assertSee('Auskunft anfordern')
            ->assertSee('Löschung anfordern')
            ->assertSee('Daten berichtigen')
            ->assertSee('Widerspruch')
            ->assertSee('Datenübertragbarkeit');

        $this->get(route('support.report'))
            ->assertOk()
            ->assertSee('Fehler melden')
            ->assertDontSee('Auskunft anfordern');
    }

    public function test_guest_privacy_request_creates_tracked_case_with_one_month_deadline(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-24 08:00:00'));

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post(route('support.store'), [
            'type' => 'privacy_access',
            'guest_name' => 'Privacy Guest',
            'guest_email' => 'privacy@example.test',
            'subject' => 'Auskunft',
            'description' => 'Bitte teilen Sie mir mit, welche personenbezogenen Daten verarbeitet werden.',
        ])->assertRedirect(route('support.thanks'));

        $ticket = DB::table('support_tickets')->where('guest_email', 'privacy@example.test')->first();

        $this->assertNotNull($ticket);
        $this->assertSame('privacy_access', $ticket->type);

        $this->assertDatabaseHas('support_ticket_privacy_cases', [
            'ticket_id' => $ticket->id,
            'identity_status' => 'pending',
            'received_at' => '2026-09-24 08:00:00',
            'original_due_at' => '2026-10-24 21:59:59',
            'due_at' => '2026-10-24 21:59:59',
        ]);
    }

    public function test_authenticated_privacy_request_does_not_require_an_additional_identity_check_by_default(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-24 08:00:00'));
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->post(route('support.store'), [
                'type' => 'privacy_rectification',
                'subject' => 'Berichtigung',
                'description' => 'Eine personenbezogene Angabe zu meinem Konto ist falsch.',
            ])
            ->assertRedirect();

        $ticketId = DB::table('support_tickets')->where('user_id', $user->id)->value('id');

        $this->assertDatabaseHas('support_ticket_privacy_cases', [
            'ticket_id' => $ticketId,
            'identity_status' => 'not_required',
        ]);
    }

    public function test_general_legal_request_is_tracked_without_inventing_a_gdpr_deadline(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-24 08:00:00'));

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.15'])
            ->post(route('support.store'), [
                'type' => 'legal_other',
                'guest_name' => 'Legal Guest',
                'guest_email' => 'legal@example.test',
                'description' => 'Ich habe eine sonstige rechtliche Anfrage.',
            ])
            ->assertRedirect(route('support.thanks'));

        $ticketId = DB::table('support_tickets')->where('guest_email', 'legal@example.test')->value('id');
        $case = DB::table('support_ticket_privacy_cases')->where('ticket_id', $ticketId)->first();

        $this->assertNotNull($case);
        $this->assertSame('manual', $case->deadline_rule);
        $this->assertNull($case->original_due_at);
        $this->assertNull($case->due_at);
    }

    public function test_regular_support_ticket_does_not_create_a_privacy_case(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.12'])
            ->post(route('support.store'), [
            'type' => 'bug',
            'guest_name' => 'Bug Guest',
            'guest_email' => 'bug@example.test',
            'description' => 'Die Kartenansicht funktioniert bei mir nicht.',
        ])->assertRedirect(route('support.thanks'));

        $ticketId = DB::table('support_tickets')->where('guest_email', 'bug@example.test')->value('id');

        $this->assertDatabaseMissing('support_ticket_privacy_cases', [
            'ticket_id' => $ticketId,
        ]);
    }

    public function test_admin_can_filter_and_manage_privacy_case_metadata(): void
    {
        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-09-24 08:00:00'));

        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['favon.owner_email' => $owner->email]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.13'])
            ->post(route('support.store'), [
            'type' => 'privacy_access',
            'guest_name' => 'Privacy Guest',
            'guest_email' => 'privacy-admin@example.test',
            'description' => 'Ich möchte Auskunft über meine Daten.',
        ]);

        $ticketId = DB::table('support_tickets')->where('guest_email', 'privacy-admin@example.test')->value('id');

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.support.index', ['privacy' => 1]))
            ->assertOk()
            ->assertSee('Privacy Guest')
            ->assertSee('Auskunft')
            ->assertSee('24.10.2026');

        $this->actingAs($owner)
            ->put(route('admin.support.update', $ticketId), [
                'status' => 'in_progress',
                'priority' => 'normal',
                'privacy_identity_status' => 'confirmed',
                'privacy_due_at' => '2026-11-24',
                'privacy_extension_reason' => 'Die Anfrage ist umfangreich und erfordert zusätzliche Prüfung.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_privacy_cases', [
            'ticket_id' => $ticketId,
            'identity_status' => 'confirmed',
            'due_at' => '2026-11-24 22:59:59',
            'extension_reason' => 'Die Anfrage ist umfangreich und erfordert zusätzliche Prüfung.',
        ]);
    }

    public function test_deadline_extension_requires_a_reason_and_is_limited_to_two_additional_months(): void
    {
        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-09-24 08:00:00'));

        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['favon.owner_email' => $owner->email]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.14'])
            ->post(route('support.store'), [
            'type' => 'privacy_deletion',
            'guest_name' => 'Delete Guest',
            'guest_email' => 'delete@example.test',
            'description' => 'Bitte prüfen Sie meinen Löschungswunsch.',
        ]);

        $ticketId = DB::table('support_tickets')->where('guest_email', 'delete@example.test')->value('id');

        $this->actingAs($owner)
            ->put(route('admin.support.update', $ticketId), [
                'status' => 'open',
                'priority' => 'normal',
                'privacy_identity_status' => 'pending',
                'privacy_due_at' => '2026-11-24',
            ])
            ->assertSessionHasErrors('privacy_extension_reason');

        $this->actingAs($owner)
            ->put(route('admin.support.update', $ticketId), [
                'status' => 'open',
                'priority' => 'normal',
                'privacy_identity_status' => 'pending',
                'privacy_due_at' => '2027-01-24',
                'privacy_extension_reason' => 'Zu lange Verlängerung',
            ])
            ->assertSessionHasErrors('privacy_due_at');
    }
}
