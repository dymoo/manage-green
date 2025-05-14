<?php

namespace Tests\Feature\Tenant\MemberManagement;

use App\Filament\Resources\UserResource; // Corrected Resource namespace
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Tenant\TenantTestCase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Models\Tenant;
use Spatie\Permission\Models\Permission;

class RolePermissionTest extends TenantTestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_user_has_admin_role_for_tenant(): void
    {
        $role = Role::where('name', 'admin')->firstOrFail(); // Get the global role
        $modelHasRolesTable = config('permission.table_names.model_has_roles');
        $teamIdField = config('permission.column_names.team_foreign_key', 'team_id');

        // Assert the user is assigned the role in the pivot table with the correct tenant ID
        $this->assertDatabaseHas($modelHasRolesTable, [
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $this->adminUser->id,
            $teamIdField => $this->tenant->id
        ]);
    }
    
    /** @test */
    public function staff_user_has_staff_role_for_tenant(): void
    {
        $staffUser = $this->createStaffUser(); // Ensure staff user is created
        $role = Role::where('name', 'staff')->firstOrFail(); 
        $modelHasRolesTable = config('permission.table_names.model_has_roles');
        $teamIdField = config('permission.column_names.team_foreign_key', 'team_id');

        $this->assertDatabaseHas($modelHasRolesTable, [
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $staffUser->id,
            $teamIdField => $this->tenant->id
        ]);
    }

    /** @test */
    public function member_user_has_member_role_for_tenant(): void
    {
        $memberUser = $this->createMemberUser(); // Ensure member user is created
        $role = Role::where('name', 'member')->firstOrFail(); 
        $modelHasRolesTable = config('permission.table_names.model_has_roles');
        $teamIdField = config('permission.column_names.team_foreign_key', 'team_id');

        $this->assertDatabaseHas($modelHasRolesTable, [
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $memberUser->id,
            $teamIdField => $this->tenant->id
        ]);
    }

    /** @test */
    public function admin_has_permission_to_manage_users(): void
    {
        $this->actingAs($this->adminUser);

        $this->tenant->makeCurrent();
        $this->assertTrue($this->adminUser->can('viewAny', User::class));
        $this->assertTrue($this->adminUser->can('create', User::class));
        $this->assertTrue($this->adminUser->can('update', $this->createMemberUser()));
        $this->assertTrue($this->adminUser->can('delete', $this->createMemberUser()));
        Tenant::forgetCurrent();
    }

    /** @test */
    public function staff_has_permission_to_manage_users(): void
    {
        $staffUser = $this->createStaffUser();
        $this->actingAs($staffUser);

        $this->tenant->makeCurrent();
        $this->assertTrue($staffUser->can('viewAny', User::class));
        $this->assertTrue($staffUser->can('create', User::class));
        $this->assertTrue($staffUser->can('update', $this->createMemberUser()));
        // Staff likely cannot delete users by default
        $this->assertFalse($staffUser->can('delete', $this->createMemberUser()));
        Tenant::forgetCurrent();
    }

    /** @test */
    public function member_cannot_manage_users(): void
    {
        $memberUser = $this->createMemberUser();
        $this->actingAs($memberUser);

        $this->tenant->makeCurrent();
        $this->assertFalse($memberUser->can('viewAny', User::class));
        $this->assertFalse($memberUser->can('create', User::class));
        $this->assertFalse($memberUser->can('update', $this->createMemberUser()));
        $this->assertFalse($memberUser->can('delete', $this->createMemberUser()));
        Tenant::forgetCurrent();
    }

    /** @test */
    public function admin_can_access_user_resource_pages(): void
    {
        $this->actingAs($this->adminUser);
        $member = $this->createMemberUser();

        $this->tenant->makeCurrent();
        $this->get(UserResource::getUrl('index'))->assertSuccessful();
        $this->get(UserResource::getUrl('create'))->assertSuccessful();
        $this->get(UserResource::getUrl('edit', ['record' => $member->getRouteKey()]))->assertSuccessful();
        Tenant::forgetCurrent();
    }

    /** @test */
    public function staff_can_access_user_resource_pages(): void
    {
        $staffUser = $this->createStaffUser();
        $this->actingAs($staffUser);
        $member = $this->createMemberUser();

        $this->tenant->makeCurrent();
        $this->get(UserResource::getUrl('index'))->assertSuccessful();
        $this->get(UserResource::getUrl('create'))->assertSuccessful();
        $this->get(UserResource::getUrl('edit', ['record' => $member->getRouteKey()]))->assertSuccessful();
        Tenant::forgetCurrent();
    }

    /** @test */
    public function member_cannot_access_user_resource_pages(): void
    {
        $memberUser = $this->createMemberUser();
        $this->actingAs($memberUser);

        $this->tenant->makeCurrent();
        $this->get(UserResource::getUrl('index'))->assertForbidden();
        $this->get(UserResource::getUrl('create'))->assertForbidden();
        // Edit might depend on if they can view their *own* profile via this resource
        $this->get(UserResource::getUrl('edit', ['record' => $memberUser->getRouteKey()]))->assertForbidden();
        Tenant::forgetCurrent();
    }
} 