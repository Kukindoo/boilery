<?php

namespace Tests\Feature;

use App\Actions\Roles\UpdateRolePermissions;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Roles\RolePermissions;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionsTest extends FeatureTestCase
{
    #[TestWith([Permissions::ASSEMBLY_ACTION_FORCE_DELETE_ALL, 'Force delete all'])]
    #[TestWith([Permissions::WORK_VIEW_OWN, 'View own'])]
    #[TestWith([Permissions::SPARE_PART_CREATE_ALL, 'Create all'])]
    #[TestWith([Permissions::USER_EDIT_ALL, 'Edit all'])]
    #[TestWith([Permissions::PDF_GENERATE_ALL, 'Generate PDF'])]
    public function test_badge_labels_omit_model_name_but_preserve_action_and_scope(Permissions $permission, string $label): void
    {
        $role = Role::create(['name' => 'Badge labels', 'guard_name' => 'web']);
        $role->givePermissionTo($permission->label());

        Livewire::actingAs(User::factory()->admin()->create())->test(RolePermissions::class, ['role' => $role])
            ->assertSeeHtml($label . "\n")
            ->assertSeeHtml('title="' . $permission->label() . '"');
    }

    public function test_permissions_card_can_stage_removal_then_save(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $original = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $role->givePermissionTo($original);
        $component = Livewire::actingAs($admin)->test(RolePermissions::class, ['role' => $role])
            ->assertSee($original->name)->assertDontSee('togglePermission(', false)
            ->call('edit')->assertSet('editing', true)->assertSee('togglePermission(', false)
            ->assertDontSee('selectedPermissions.', false)
            ->call('togglePermission', $original->id)->assertSee('line-through', false);

        $this->assertTrue($role->fresh()->hasPermissionTo($original));
        $component->call('save')->assertHasNoErrors()->assertSet('editing', false)->assertDontSee('togglePermission(', false);
        $this->assertFalse($role->fresh()->hasPermissionTo($original));
        $this->assertSame('Technician', $role->fresh()->name);
    }

    public function test_cancel_discards_changes_and_cross_can_restore_pending_removal(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $permission = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $role->givePermissionTo($permission);
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(RolePermissions::class, ['role' => $role])
            ->call('edit')->call('togglePermission', $permission->id)->assertSet('permissionIds', [])
            ->call('togglePermission', $permission->id)->assertSet('permissionIds', [$permission->id])
            ->call('togglePermission', $permission->id)->call('cancel')->assertSet('editing', false);

        $component->call('edit')->assertSet('permissionIds', [$permission->id]);
        $this->assertTrue($role->fresh()->hasPermissionTo($permission));
    }

    public function test_edit_mode_shows_plus_badges_and_additions_only_persist_on_save(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $assigned = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $unassigned = Permission::findByName(Permissions::USER_VIEW_OWN->label());
        $role->givePermissionTo($assigned);

        $component = Livewire::actingAs(User::factory()->admin()->create())->test(RolePermissions::class, ['role' => $role])
            ->assertDontSee($unassigned->name)->call('edit')->assertSee($assigned->name)->assertSee($unassigned->name)
            ->assertSeeHtml('aria-label="Přidat oprávnění ' . $unassigned->name . '"')
            ->assertDontSee('data-flux-select', false)
            ->call('togglePermission', $unassigned->id)
            ->assertDontSeeHtml('aria-label="Přidat oprávnění ' . $unassigned->name . '"');
        $this->assertFalse($role->fresh()->hasPermissionTo($unassigned));

        $component->call('save')->assertHasNoErrors();
        $this->assertTrue($role->fresh()->hasPermissionTo($unassigned));
        $this->assertTrue($role->fresh()->hasPermissionTo($assigned));
    }

    public function test_cancel_discards_additions_and_other_guards_cannot_be_added(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $permission = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $api = Permission::create(['name' => 'API permission', 'guard_name' => 'api']);
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(RolePermissions::class, ['role' => $role])
            ->call('edit')->assertDontSee($api->name)->call('togglePermission', $permission->id)
            ->call('cancel')->assertDontSee($permission->name);
        $this->assertSame(0, $role->permissions()->count());

        $component->call('edit')->call('togglePermission', $api->id)->assertNotFound();
        Livewire::test(RolePermissions::class, ['role' => $role])->call('edit')->call('togglePermission', 999999)->assertNotFound();
    }

    public function test_selected_permissions_sort_first_and_update_when_toggled(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $view = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $restore = Permission::findByName(Permissions::WORK_RESTORE_ALL->label());
        $create = Permission::findByName(Permissions::WORK_CREATE_ALL->label());
        $role->givePermissionTo([$view, $restore]);

        $component = Livewire::actingAs(User::factory()->admin()->create())->test(RolePermissions::class, ['role' => $role])
            ->call('edit')->assertViewHas('permissionScopes', fn ($groups): bool => $groups['works']['all']->take(2)->pluck('id')->all() === [$restore->id, $view->id]);
        $component->call('togglePermission', $create->id)
            ->assertViewHas('permissionScopes', fn ($groups): bool => $groups['works']['all']->take(3)->pluck('id')->all() === [$create->id, $restore->id, $view->id]);
        $component->call('togglePermission', $restore->id)
            ->assertViewHas('permissionScopes', fn ($groups): bool => $groups['works']['all']->take(3)->pluck('id')->all() === [$create->id, $view->id, $restore->id]);
    }

    public function test_saving_only_changes_assignments_and_keeps_role_metadata_intact(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $original = $role->fresh()->getAttributes();
        $this->travel(1)->minutes();
        $permission = Permission::findByName(Permissions::WORK_VIEW_ALL->label());

        Livewire::actingAs(User::factory()->admin()->create())->test(RolePermissions::class, ['role' => $role])
            ->call('edit')->call('togglePermission', $permission->id)->call('save')->assertHasNoErrors();

        $this->assertSame($original, $role->refresh()->getAttributes());
        $this->assertTrue($role->hasPermissionTo($permission));
    }

    public function test_assignment_action_rejects_other_guards_without_changing_existing_permissions(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $permission = Permission::findByName(Permissions::WORK_VIEW_ALL->label());
        $role->givePermissionTo($permission);
        $apiPermission = Permission::create(['name' => 'API permission', 'guard_name' => 'api']);
        $this->actingAs(User::factory()->admin()->create());

        try {
            app(UpdateRolePermissions::class)->handle($role, [$apiPermission->id]);
            $this->fail('Expected invalid permissions to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('permissionIds', $exception->errors());
        }

        $this->assertSame([$permission->id], $role->permissions()->pluck('permissions.id')->all());
    }

    public function test_changes_require_edit_mode(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(RolePermissions::class, ['role' => $role])->call('save')->assertForbidden();
        Livewire::test(RolePermissions::class, ['role' => $role])->call('togglePermission', 1)->assertForbidden();
    }

    public function test_non_admin_cannot_mount_and_revoked_admin_cannot_save(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        Livewire::actingAs(User::factory()->customer()->create())->test(RolePermissions::class, ['role' => $role])->assertForbidden();
        $admin = User::factory()->admin()->create();
        $component = Livewire::actingAs($admin)->test(RolePermissions::class, ['role' => $role])->call('edit');
        $admin->removeRole(Roles::ADMIN->value);

        $component->call('save')->assertForbidden();
        $this->assertSame(0, $role->permissions()->count());
    }
}
