<?php

namespace Tests\Feature;

use App\Actions\Permissions\UpdatePermissionRoles;
use App\Enums\Permissions;
use App\Livewire\Permissions\PermissionRoles;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionRolesTest extends FeatureTestCase
{
    public function test_role_changes_are_staged_and_only_assignments_are_saved(): void
    {
        $permission = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $other = Permission::findByName(Permissions::WORK_VIEW_OWN->label());
        $assigned = Role::create(['name' => 'Assigned technician', 'guard_name' => 'web']);
        $available = Role::create(['name' => 'Available technician', 'guard_name' => 'web']);
        $assigned->givePermissionTo([$permission, $other]);
        $available->givePermissionTo($other);
        $metadata = $permission->refresh()->getAttributes();
        $this->travel(1)->minutes();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('permissions.show', $permission))->assertOk()->assertSeeLivewire(PermissionRoles::class);
        $component = Livewire::actingAs($admin)->test(PermissionRoles::class, ['permission' => $permission])
            ->assertSee($assigned->name)->assertDontSee($available->name)
            ->call('edit')->assertSeeHtml('aria-label="Přidat roli ' . $available->name . '"')
            ->call('toggleRole', $assigned->id)->assertSeeHtml('line-through')
            ->call('toggleRole', $available->id);

        $this->assertTrue($assigned->fresh()->hasPermissionTo($permission));
        $this->assertFalse($available->fresh()->hasPermissionTo($permission));
        $component->call('save')->assertHasNoErrors()->assertSet('editing', false);
        $this->assertFalse($assigned->fresh()->hasPermissionTo($permission));
        $this->assertTrue($available->fresh()->hasPermissionTo($permission));
        $this->assertTrue($assigned->fresh()->hasPermissionTo($other));
        $this->assertTrue($available->fresh()->hasPermissionTo($other));
        $this->assertSame($metadata, $permission->refresh()->getAttributes());
    }

    public function test_cancel_discards_changes_and_assigned_roles_sort_first(): void
    {
        $permission = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $available = Role::create(['name' => 'A available', 'guard_name' => 'web']);
        $assigned = Role::create(['name' => 'Z assigned', 'guard_name' => 'web']);
        $assigned->givePermissionTo($permission);
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(PermissionRoles::class, ['permission' => $permission])
            ->call('edit')->assertViewHas('roles', fn ($roles): bool => $roles->first()->id !== $available->id)
            ->call('toggleRole', $assigned->id)->call('toggleRole', $assigned->id)
            ->call('toggleRole', $available->id)->call('cancel')->assertSet('editing', false)
            ->assertDontSee($available->name);
        $this->assertFalse($available->fresh()->hasPermissionTo($permission));
        $component->call('edit')->assertViewHas('roles', fn ($roles): bool => $roles->values()->search(fn (Role $role): bool => $role->id === $assigned->id) < $roles->values()->search(fn (Role $role): bool => $role->id === $available->id));
    }

    public function test_view_permission_does_not_allow_assignment_changes(): void
    {
        $user = User::factory()->customer()->create();
        $user->givePermissionTo(Permissions::PERMISSION_VIEW_ALL->label());
        $permission = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        Livewire::actingAs($user)->test(PermissionRoles::class, ['permission' => $permission])
            ->assertDontSee('Upravit role')->call('edit')->assertForbidden();
        Livewire::test(PermissionRoles::class, ['permission' => $permission])->call('save')->assertForbidden();
        Livewire::test(PermissionRoles::class, ['permission' => $permission])->call('toggleRole', 11)->assertForbidden();
    }

    public function test_other_guards_and_changes_outside_edit_mode_are_rejected(): void
    {
        $permission = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $api = Role::create(['name' => 'API role', 'guard_name' => 'api']);
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(PermissionRoles::class, ['permission' => $permission])->call('save')->assertForbidden();
        Livewire::test(PermissionRoles::class, ['permission' => $permission])->call('edit')->assertDontSee($api->name)
            ->call('toggleRole', $api->id)->assertNotFound();
        $before = $permission->roles()->pluck('roles.id')->all();
        try {
            app(UpdatePermissionRoles::class)->handle($permission, [$api->id]);
            $this->fail('Expected invalid roles to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('roleIds', $exception->errors());
        }
        $this->assertSame($before, $permission->roles()->pluck('roles.id')->all());
    }

    public function test_removing_own_permission_to_view_redirects_after_save(): void
    {
        $permission = Permission::findByName(Permissions::PERMISSION_VIEW_ALL->label());
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(PermissionRoles::class, ['permission' => $permission])
            ->call('edit')->call('toggleRole', $admin->roles->first()->id)->call('save')
            ->assertHasNoErrors()->assertRedirect(route('dashboard'));
    }
}
