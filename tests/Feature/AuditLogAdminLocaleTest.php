<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLogAdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_is_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create([
            'name' => 'Audit Actor',
            'email_verified_at' => now(),
        ]);
        config(['favon.owner_email' => $owner->email]);

        DB::table('audit_logs')->insert([
            'user_id' => $owner->id,
            'entity_type' => 'photo',
            'entity_id' => 42,
            'action' => 'photo_approved',
            'source' => 'admin',
            'old_values' => json_encode(['status' => 'pending']),
            'new_values' => json_encode(['status' => 'approved']),
            'internal_comment' => 'Original audit comment',
            'created_at' => now(),
        ]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('Nachvollziehbare Änderungen und Moderationsaktionen im System.')
            ->assertSee('Foto freigegeben')
            ->assertSee('Administration')
            ->assertSee('Original audit comment');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('Traceable changes and moderation actions in the system.')
            ->assertSee('Photo approved')
            ->assertSee('Object type')
            ->assertSee('Before')
            ->assertSee('After')
            ->assertSee('Original audit comment');
    }
}
