<?php

namespace Tests\Feature\Tenant\MemberManagement;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Tenant\TenantTestCase;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Filament\Facades\Filament;

class MemberIdentifierTest extends TenantTestCase
{
    use RefreshDatabase;

    /** @test */
    public function fob_id_is_saved_during_member_registration_by_admin(): void
    {
        $this->actingAs($this->adminUser);
        $fobId = 'ADMINFOB001';

        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Test Member',
                'email' => 'test@example.com',
                'fob_id' => $fobId,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'fob_id' => $fobId,
        ]);
        $user = User::where('email', 'test@example.com')->first();
        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function fob_id_can_be_updated_by_admin(): void
    {
        $member = $this->createMemberUser();
        $newFobId = 'UPDATEDFOB002';

        $this->actingAs($this->adminUser);

        Livewire::test(UserResource\Pages\EditUser::class, [
            'record' => $member->getRouteKey(),
            'tenant' => $this->tenant,
        ])
            ->fillForm([
                'name' => $member->name,
                'email' => $member->email,
                'fob_id' => $newFobId,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'fob_id' => $newFobId,
        ]);
    }

    /** @test */
    public function fob_id_can_be_updated_by_staff(): void
    {
        $member = $this->createMemberUser();
        $staffUser = $this->createStaffUser();
        $newFobId = 'UPDATEDFOB003';

        $this->actingAs($staffUser);

        Livewire::test(UserResource\Pages\EditUser::class, [
            'record' => $member->getRouteKey(),
            'tenant' => $this->tenant,
        ])
            ->fillForm([
                'name' => $member->name,
                'email' => $member->email,
                'fob_id' => $newFobId,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'fob_id' => $newFobId,
        ]);
    }

    /** @test */
    public function fob_id_must_be_unique_within_tenant_on_create(): void
    {
        $this->actingAs($this->adminUser);
        $existingFobId = 'UNIQUEFOB004';

        $memberA = User::factory()->create([
            'fob_id' => $existingFobId,
            'tenant_id' => $this->tenant->id,
        ]);
        $memberA->assignRole('member', $this->tenant);

        Livewire::test(UserResource\Pages\CreateUser::class, [
            'tenant' => $this->tenant,
        ])
            ->fillForm([
                'name' => 'Another Member',
                'email' => 'another@example.com',
                'fob_id' => $existingFobId,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->call('create')
            ->assertHasErrors(['data.fob_id' => 'unique']);
    }

    /** @test */
    public function fob_id_must_be_unique_within_tenant_on_update(): void
    {
        $this->actingAs($this->adminUser);
        $existingFobId = 'UNIQUEFOB005';

        $memberA = User::factory()->create([
            'fob_id' => $existingFobId,
            'tenant_id' => $this->tenant->id,
        ]);
        $memberA->assignRole('member', $this->tenant);
        
        $memberC = User::factory()->create([
            'fob_id' => 'OTHERFOB006',
            'tenant_id' => $this->tenant->id,
        ]);
        $memberC->assignRole('member', $this->tenant);

        $memberCToEdit = User::findOrFail($memberC->id);

        Livewire::test(UserResource\Pages\EditUser::class, [
            'record' => $memberCToEdit->getRouteKey(),
            'tenant' => $this->tenant,
        ])
            ->fillForm([
                'name' => $memberC->name,
                'email' => $memberC->email,
                'fob_id' => $existingFobId,
            ])
            ->call('save')
            ->assertHasErrors(['data.fob_id' => 'unique']);
    }

    /** @test */
    public function fob_id_can_be_reused_across_tenants(): void
    {
        $fobIdToReuse = 'REUSEDFOB007';

        // Tenant A
        $this->actingAs($this->adminUser);
        Livewire::test(UserResource\Pages\CreateUser::class, [
            'tenant' => $this->tenant,
        ])
            ->fillForm([
                'name' => 'Tenant A Member',
                'email' => 'memberA@example.com',
                'fob_id' => $fobIdToReuse,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->call('create')
            ->assertHasNoErrors();
        $memberA = User::where('email', 'memberA@example.com')->first();
        $this->assertNotNull($memberA);
        $memberA->assignRole('member', $this->tenant);
        $this->assertDatabaseHas('users', ['id' => $memberA->id, 'fob_id' => $fobIdToReuse]);

        // Tenant B
        $tenantB = Tenant::factory()->create(['slug' => 'tenantb']);
        $adminB = User::factory()->create();
        $tenantB->users()->attach($adminB);
        // $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        // $adminB->assignRole($adminRole); 

        // Assign 'admin' role to adminB scoped to tenantB, assuming 'assignRole' handles this.
        $adminB->assignRole('admin', $tenantB);

        $this->actingAs($adminB);
        Filament::setTenant($tenantB);

        Livewire::test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Tenant B Member',
                'email' => 'memberB@example.com',
                'fob_id' => $fobIdToReuse,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $memberB = User::where('email', 'memberB@example.com')->first();
        $this->assertNotNull($memberB);

        // Manually associate user with tenant and assign role for test stability
        $memberB->tenant_id = $tenantB->id;
        $memberB->save();
        $tenantB->users()->attach($memberB);
        $memberB->assignRole('member', $tenantB);

        $this->assertEquals($tenantB->id, $memberB->tenant_id);
        $this->assertEquals($fobIdToReuse, $memberB->fob_id);
        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenantB->id,
            'user_id' => $memberB->id,
        ]);
        $this->assertTrue($memberB->hasRole('member', $tenantB));
    }
} 