<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function ownerAndTarget(): array
    {
        $this->seed(DatabaseSeeder::class);
        $owner=User::factory()->create(['email_verified_at'=>now()]);
        $target=User::factory()->create(['name'=>'Target User','email'=>'target@example.test','email_verified_at'=>now()]);
        config(['favon.owner_email'=>$owner->email]);
        return [$owner,$target];
    }

    public function test_owner_can_search_and_edit_account_identity(): void
    {
        [$owner,$target]=$this->ownerAndTarget();
        $this->actingAs($owner)->get(route('admin.users.index',['q'=>'target@example.test']))->assertOk()->assertSee('Target User');
        $this->actingAs($owner)->put(route('admin.users.account.update',$target),['name'=>'Corrected User','email'=>'new@example.test','public_alias'=>'Corrected.User'])->assertRedirect();
        $this->assertDatabaseHas('users',['id'=>$target->id,'name'=>'Corrected User','email'=>'new@example.test','email_verified_at'=>null]);
        $this->assertDatabaseHas('user_profiles',['user_id'=>$target->id,'public_alias'=>'Corrected.User']);
        $audit = DB::table('audit_logs')->where('entity_type','user_account')->where('entity_id',$target->id)->where('action','account_updated')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString('target@example.test', (string) $audit->old_values);
        $this->assertStringNotContainsString('new@example.test', (string) $audit->new_values);
        $this->assertStringNotContainsString('Target User', (string) $audit->old_values);
        $this->assertStringNotContainsString('Corrected User', (string) $audit->new_values);
    }

    public function test_owner_can_manually_verify_email_and_action_is_audited(): void
    {
        [$owner,$target]=$this->ownerAndTarget();
        DB::table('users')->where('id',$target->id)->update(['email_verified_at'=>null]);

        $this->actingAs($owner)
            ->post(route('admin.users.email.verify',$target))
            ->assertRedirect();

        $this->assertNotNull($target->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs',[
            'entity_type'=>'user_account',
            'entity_id'=>$target->id,
            'action'=>'email_manually_verified',
            'source'=>'admin',
        ]);
    }

    public function test_owner_can_suspend_and_unsuspend_user(): void
    {
        [$owner,$target]=$this->ownerAndTarget();
        $this->actingAs($owner)->post(route('admin.users.suspend',$target),['reason'=>'Test suspension'])->assertRedirect();
        $this->assertDatabaseHas('users',['id'=>$target->id,'account_status'=>'suspended','suspension_reason'=>'Test suspension']);
        $this->actingAs($target)->get(route('dashboard'))->assertForbidden()->assertDontSee('Test suspension');
        $this->actingAs($owner)->delete(route('admin.users.unsuspend',$target))->assertRedirect();
        $this->assertDatabaseHas('users',['id'=>$target->id,'account_status'=>'active','suspension_reason'=>null]);
    }

    public function test_admin_deletion_uses_immediate_anonymization(): void
    {
        [$owner,$target]=$this->ownerAndTarget();
        $this->actingAs($owner)->delete(route('admin.users.account.delete',$target),['confirmation'=>'Target User'])->assertRedirect(route('admin.users.index'));
        $row=DB::table('users')->where('id',$target->id)->first();
        $this->assertSame('deleted',$row->account_status);
        $this->assertSame('Deleted User',$row->name);
        $this->assertStringEndsWith('@deleted.invalid',$row->email);
        $audit = DB::table('audit_logs')->where('entity_type','user_account')->where('entity_id',$target->id)->where('action','account_deleted')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString('Target User', (string) $audit->old_values);
        $this->assertStringNotContainsString('target@example.test', (string) $audit->old_values);
    }

    public function test_owner_email_cannot_be_changed_from_admin_user_management(): void
    {
        [$owner]=$this->ownerAndTarget();

        $this->actingAs($owner)->put(route('admin.users.account.update',$owner),[
            'name'=>$owner->name,
            'email'=>'changed-owner@example.test',
            'public_alias'=>null,
        ])->assertSessionHasErrors('email');

        $this->assertSame($owner->email, $owner->fresh()->email);
    }

    public function test_owner_cannot_be_suspended_or_deleted(): void
    {
        [$owner]=$this->ownerAndTarget();
        $this->actingAs($owner)->post(route('admin.users.suspend',$owner),['reason'=>'No'])->assertForbidden();
        $this->actingAs($owner)->delete(route('admin.users.account.delete',$owner),['confirmation'=>$owner->name])->assertForbidden();
    }
}
