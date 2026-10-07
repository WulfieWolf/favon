<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbuseRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_support_submission_is_rate_limited(): void
    {
        $this->seed(DatabaseSeeder::class);

        for ($i = 1; $i <= 2; $i++) {
            $this->post(route('support.store'), [
                'type' => 'other',
                'description' => 'Legitime Testmeldung Nummer '.$i,
                'guest_name' => 'Rate Limit Test',
                'guest_email' => 'ratelimit@example.test',
                'website' => '',
            ])->assertRedirect(route('support.thanks'));
        }

        $this->post(route('support.store'), [
            'type' => 'other',
            'description' => 'Diese Meldung muss gedrosselt werden.',
            'guest_name' => 'Rate Limit Test',
            'guest_email' => 'ratelimit@example.test',
            'website' => '',
        ])->assertStatus(429);
    }

    public function test_public_read_rate_limit_is_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('favon.security.public_read_limit_enabled'));
    }
}
