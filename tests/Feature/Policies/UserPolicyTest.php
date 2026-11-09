<?php

namespace Tests\Feature\Policies;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class UserPolicyTest extends FeatureTestCase
{
    public static function permissionCases(): array
    {
        return [
            'no permissions' => [[], false, false],
            'view only' => [[Permissions::USER_VIEW_ALL], true, false],
            'edit only' => [[Permissions::USER_EDIT_ALL], false, true],
            'view and edit' => [[Permissions::USER_VIEW_ALL, Permissions::USER_EDIT_ALL], true, true],
            'unrelated permission' => [[Permissions::WORK_EDIT_ALL], false, false],
        ];
    }

    public static function allAndOwnPermissions(): array
    {
        $cases = [];
        foreach (['view' => 'VIEW', 'update' => 'EDIT', 'delete' => 'DELETE', 'restore' => 'RESTORE', 'forceDelete' => 'FORCE_DELETE'] as $ability => $name) {
            foreach (['none', 'ALL', 'OWN'] as $scope) {
                $cases[mb_strtolower($ability . ' ' . $scope)] = [
                    $ability,
                    $scope === 'none' ? null : constant(Permissions::class . '::USER_' . $name . '_' . $scope),
                    $scope !== 'none',
                    $scope === 'ALL',
                ];
            }
        }

        return $cases;
    }

    #[DataProvider('permissionCases')]
    public function test_access_is_determined_by_permissions(array $permissions, bool $canView, bool $canEdit): void
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission->label());
        }
        $customer = User::factory()->customer()->create();
        $nonCustomer = User::factory()->create();
        $gate = Gate::forUser($user);

        $this->assertSame($canView, $gate->allows('viewAny', User::class));
        $this->assertSame($canView, $gate->allows('view', $customer));
        $this->assertSame($canEdit, $gate->allows('update', $customer));
        $this->assertSame($canView, $gate->allows('view', $nonCustomer));
        $this->assertSame($canEdit, $gate->allows('update', $nonCustomer));
    }

    public function test_staff_role_without_permissions_does_not_grant_access(): void
    {
        $customer = User::factory()->customer()->create();

        foreach ([Roles::ADMIN, Roles::STAKEHOLDER] as $role) {
            Role::findById($role->value, 'web')->revokePermissionTo([
                Permissions::USER_VIEW_ALL->label(), Permissions::USER_EDIT_ALL->label(),
            ]);
            $staff = User::factory()->create()->assignRole($role->value);
            $gate = Gate::forUser($staff);

            $this->assertFalse($gate->allows('viewAny', User::class));
            $this->assertFalse($gate->allows('view', $customer));
            $this->assertFalse($gate->allows('update', $customer));
        }
    }

    #[DataProvider('allAndOwnPermissions')]
    public function test_all_and_own_permissions_respect_record_ownership(string $ability, ?Permissions $permission, bool $ownAllowed, bool $otherAllowed): void
    {
        $user = User::factory()->create();
        if ($permission !== null) {
            $user->givePermissionTo($permission->label());
        }
        $other = User::factory()->customer()->create();
        $gate = Gate::forUser($user);

        $this->assertSame($ownAllowed, $gate->allows($ability, $user));
        $this->assertSame($otherAllowed, $gate->allows($ability, $other));
    }

    public function test_legacy_index_permission_does_not_allow_listing_users(): void
    {
        $permission = \Spatie\Permission\Models\Permission::findOrCreate('View index users', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
        $this->actingAs($user)->get(route('customers.index'))->assertForbidden();
    }

    #[TestWith([null, false])]
    #[TestWith([Permissions::USER_EDIT_ALL, true])]
    #[TestWith([Permissions::USER_EDIT_OWN, false])]
    public function test_role_assignment_requires_edit_all_users_permission(?Permissions $permission, bool $allowed): void
    {
        $actor = User::factory()->create();
        if ($permission !== null) {
            $role = Role::create(['name' => 'Delegated role', 'guard_name' => 'web']);
            $role->givePermissionTo($permission->label());
            $actor->assignRole($role);
        }
        $target = User::factory()->create();

        $this->assertSame($allowed, Gate::forUser($actor)->allows('manageRoles', $target));
    }

    public function test_admin_without_role_assignment_permission_cannot_manage_user_roles(): void
    {
        Role::findById(Roles::ADMIN->value)->revokePermissionTo(Permissions::USER_EDIT_ALL->label());
        $admin = User::factory()->admin()->create();

        $this->assertFalse(Gate::forUser($admin)->allows('manageRoles', User::factory()->create()));
    }

    public function test_customer_own_permissions_do_not_allow_listing_all_users(): void
    {
        $user = User::factory()->customer()->create();
        $gate = Gate::forUser($user);

        $this->assertFalse($gate->allows('viewAny', User::class));
        $this->assertTrue($gate->allows('create', User::class));
        $this->assertTrue($gate->allows('view', $user));
        $this->assertTrue($gate->allows('update', $user));
    }
}
