<?php

namespace Tests\Feature;

use App\Jobs\GenerateUserDataExport;
use App\Models\User;
use App\Services\UserDataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UserDataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_request_one_queued_export(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('data-export.request'))
            ->assertRedirect()
            ->assertSessionHas('ui_toast');

        $this->assertDatabaseHas('data_export_requests', [
            'user_id' => $user->id,
            'status' => 'queued',
        ]);

        Queue::assertPushed(GenerateUserDataExport::class);
    }

    public function test_second_export_is_blocked_while_one_is_active(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        DB::table('data_export_requests')->insert([
            'user_id' => $user->id,
            'token' => '11111111-1111-4111-8111-111111111111',
            'status' => 'processing',
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('data-export.request'))
            ->assertSessionHasErrors('export');

        Queue::assertNothingPushed();
    }

    public function test_ready_export_starts_seven_day_cooldown(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        DB::table('data_export_requests')->insert([
            'user_id' => $user->id,
            'token' => '22222222-2222-4222-8222-222222222222',
            'status' => 'ready',
            'requested_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $next = app(UserDataExportService::class)->nextAvailableAt((int) $user->id);

        $this->assertNotNull($next);
        $this->assertTrue($next->isFuture());
    }

    public function test_user_cannot_download_another_users_export(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);

        DB::table('data_export_requests')->insert([
            'user_id' => $owner->id,
            'token' => '33333333-3333-4333-8333-333333333333',
            'status' => 'ready',
            'storage_path' => 'data-exports/test.zip',
            'completed_at' => now(),
            'expires_at' => now()->addHour(),
            'requested_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($other)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('data-export.download', '33333333-3333-4333-8333-333333333333'))
            ->assertNotFound();
    }
}
