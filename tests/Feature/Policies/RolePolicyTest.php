<?php

namespace Tests\Feature\Policies;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class RolePolicyTest extends FeatureTestCase
{
    #[TestWith([null, false, false])]
    #[TestWith([Permissions::ROLE_VIEW_ALL, true, false])]
    #[TestWith([Permissions::ROLE_EDIT_ALL, false, true])]
    #[TestWith([Permissions::PERMISSION_VIEW_ALL, false, false])]
    #[TestWith([Permissions::USER_EDIT_ALL, false, false])]
    public function test_role_abilities_depend_on_permissions_without_admin_role(?Permissions $permission, bool $view, bool $edit): void
    {
        $user = User::factory()->create();
        if ($permission !== null) {
            $role = Role::create(['name' => 'Delegated role', 'guard_name' => 'web']);
            $role->givePermissionTo($permission->label());
            $user->assignRole($role);
        }
        $target = Role::findById(Roles::CUSTOMER->value);
        $gate = Gate::forUser($user);

        $this->assertSame($view, $gate->allows('viewAny', Role::class));
        $this->assertSame($view, $gate->allows('view', $target));
        $this->assertSame($edit, $gate->allows('managePermissions', $target));
        $this->assertFalse($gate->allows('update', $target));
    }

    public function test_admin_identity_does_not_bypass_missing_permissions_or_guard_checks(): void
    {
        $adminRole = Role::findById(Roles::ADMIN->value);
        $adminRole->syncPermissions([]);
        $admin = User::factory()->admin()->create();
        $target = Role::findById(Roles::CUSTOMER->value);

        $this->assertFalse(Gate::forUser($admin)->allows('view', $target));
        $this->assertFalse(Gate::forUser($admin)->allows('managePermissions', $target));
        $adminRole->givePermissionTo([Permissions::ROLE_VIEW_ALL->label(), Permissions::ROLE_EDIT_ALL->label()]);
        $api = Role::create(['name' => 'API role', 'guard_name' => 'api']);
        $this->assertFalse(Gate::forUser($admin->fresh())->allows('view', $api));
        $this->assertFalse(Gate::forUser($admin->fresh())->allows('managePermissions', $api));
    }
}
