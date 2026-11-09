<?php

namespace Tests\Feature;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\AssemblyActions\AssemblyActionCreate;
use App\Livewire\AssemblyActions\AssemblyActionEdit;
use App\Livewire\AssemblyActions\WorkAssemblyActions;
use App\Models\AssemblyAction;
use App\Models\User;
use App\Models\Work;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AssemblyActionsPagesTest extends FeatureTestCase
{
    #[TestWith([11])]
    #[TestWith([21])]
    public function test_staff_roles_can_access_crud_and_assembly_actions(int $roleId): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findById($roleId));
        Role::findById($roleId)->givePermissionTo(Permissions::WORK_VIEW_ALL->label());
        $action = AssemblyAction::factory()->create(['code' => '<script>code</script>', 'name' => '<script>name</script>', 'description' => '<script>description</script>']);
        $work = Work::factory()->create();

        $this->actingAs($user)->get(route('assembly-actions.index'))->assertOk()->assertSee('Pracovní úkony');
        $this->get(route('assembly-actions.create'))->assertSeeLivewire(AssemblyActionCreate::class);
        $this->get(route('assembly-actions.edit', $action))->assertSeeLivewire(AssemblyActionEdit::class);
        $this->get(route('assembly-actions.show', $action))->assertOk()
            ->assertSee($action->code)->assertDontSee($action->code, false)
            ->assertSee($action->name)->assertDontSee($action->name, false)
            ->assertSee($action->description)->assertDontSee($action->description, false);
        $this->get(route('works.show', $work))->assertSeeLivewire(WorkAssemblyActions::class);
        $this->get(route('assembly-actions.show', 999999))->assertNotFound();
        $this->get(route('assembly-actions.edit', 999999))->assertNotFound();
    }

    public function test_pages_require_authentication_and_customer_cannot_access_actions_even_with_permissions(): void
    {
        $action = AssemblyAction::factory()->create();
        foreach (['index', 'create', 'show', 'edit'] as $page) {
            $this->get(route('assembly-actions.' . $page, $action))->assertRedirect(route('login'));
        }
        $customer = User::factory()->create();
        $customer->assignRole(Role::findById(Roles::CUSTOMER->value));
        $customer->givePermissionTo([Permissions::ASSEMBLY_ACTION_VIEW_ALL->label(), Permissions::WORK_VIEW_OWN->label()]);
        $work = Work::factory()->for($customer)->create();
        $this->actingAs($customer);
        foreach (['index', 'create', 'show', 'edit'] as $page) {
            $this->get(route('assembly-actions.' . $page, $action))->assertForbidden();
        }
        $this->get(route('works.show', $work))->assertOk()->assertSeeLivewire(WorkAssemblyActions::class);
    }

    public function test_permissions_are_provisioned_only_to_staff_roles(): void
    {
        foreach (Permissions::cases() as $permission) {
            if (! str_starts_with($permission->name, 'ASSEMBLY_ACTION_')) {
                continue;
            }
            $this->assertTrue(Role::findById(Roles::ADMIN->value)->hasPermissionTo($permission->label()));
            $this->assertTrue(Role::findById(Roles::STAKEHOLDER->value)->hasPermissionTo($permission->label()));
            $this->assertFalse(Role::findById(Roles::CUSTOMER->value)->hasPermissionTo($permission->label()));
        }
    }

    public function test_permission_migration_is_idempotent_and_preserves_existing_grants(): void
    {
        $permission = Permission::findByName(Permissions::ASSEMBLY_ACTION_VIEW_ALL->label(), 'web');
        $permissionId = $permission->id;
        $migration = require database_path('migrations/2026_11_08_161729_add_assembly_action_permissions.php');
        $migration->up();
        $migration->up();

        $this->assertSame($permissionId, Permission::findByName(Permissions::ASSEMBLY_ACTION_VIEW_ALL->label(), 'web')->id);
        $this->assertSame(1, Permission::query()->where('name', $permission->name)->where('guard_name', 'web')->count());
        $this->assertTrue(Role::findById(Roles::ADMIN->value)->hasPermissionTo($permission->name));
        $this->assertTrue(Role::findById(Roles::STAKEHOLDER->value)->hasPermissionTo($permission->name));
    }
}
