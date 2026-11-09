<?php

namespace Tests\Feature;

use App\Actions\Customers\UpdateUserRoles;
use App\Enums\Roles;
use App\Livewire\Customers\UserRoles;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

class UserRolesTest extends FeatureTestCase
{
    public function test_user_sees_roles_on_own_show_page_without_edit_controls(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)->get(route('customers.show', $user))->assertSeeLivewire(UserRoles::class)->assertSee('Zákazník');
        Livewire::test(UserRoles::class, ['user' => $user])->assertSee('Zákazník')->assertDontSee('Upravit role')
            ->assertDontSee('toggleRole(', false)->assertDontSee('Admin');
    }

    public function test_admin_can_stage_role_additions_and_removals_then_save(): void
    {
        $user = User::factory()->customer()->create();
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(UserRoles::class, ['user' => $user])
            ->assertDontSee('toggleRole(', false)->assertDontSee('Technician')
            ->call('edit')->assertSet('editing', true)->assertSee('toggleRole(', false)
            ->assertSeeHtml('aria-label="Přidat roli Technician"')->assertDontSee('data-flux-select', false)
            ->call('toggleRole', $role->id)->call('toggleRole', Roles::CUSTOMER->value)->assertSee('line-through', false);

        $this->assertFalse($user->fresh()->hasRole($role));
        $this->assertTrue($user->fresh()->hasRole(Roles::CUSTOMER->value));
        $component->call('save')->assertHasNoErrors()->assertSet('editing', false)->assertDontSee('toggleRole(', false);
        $this->assertTrue($user->fresh()->hasRole($role));
        $this->assertFalse($user->fresh()->hasRole(Roles::CUSTOMER->value));
    }

    public function test_cancel_discards_changes_and_pending_removals_can_be_restored(): void
    {
        $user = User::factory()->customer()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(UserRoles::class, ['user' => $user])
            ->call('edit')->call('toggleRole', Roles::CUSTOMER->value)->assertSet('roleIds', [])
            ->call('toggleRole', Roles::CUSTOMER->value)->assertSet('roleIds', [Roles::CUSTOMER->value])
            ->call('toggleRole', Roles::ADMIN->value)->call('cancel')->assertSet('editing', false);

        $component->call('edit')->assertSet('roleIds', [Roles::CUSTOMER->value]);
        $this->assertFalse($user->fresh()->hasRole(Roles::ADMIN->value));
    }

    public function test_customer_cannot_enter_edit_mode_or_call_assignment_actions(): void
    {
        $user = User::factory()->customer()->create();
        Livewire::actingAs($user)->test(UserRoles::class, ['user' => $user])->call('edit')->assertForbidden();
        Livewire::test(UserRoles::class, ['user' => $user])->call('toggleRole', Roles::ADMIN->value)->assertForbidden();
        Livewire::test(UserRoles::class, ['user' => $user])->call('save')->assertForbidden();
        $this->assertFalse($user->fresh()->hasRole(Roles::ADMIN->value));
        $this->assertTrue($user->fresh()->hasRole(Roles::CUSTOMER->value));
    }

    public function test_user_cannot_view_another_users_roles(): void
    {
        $user = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        Livewire::actingAs($user)->test(UserRoles::class, ['user' => $other])->assertForbidden();
    }

    public function test_role_changes_require_edit_mode_and_valid_web_roles(): void
    {
        $user = User::factory()->customer()->create();
        $apiRole = Role::create(['name' => 'API role', 'guard_name' => 'api']);
        Livewire::actingAs(User::factory()->admin()->create())->test(UserRoles::class, ['user' => $user])
            ->call('toggleRole', Roles::ADMIN->value)->assertForbidden();
        Livewire::test(UserRoles::class, ['user' => $user])->call('save')->assertForbidden();
        Livewire::test(UserRoles::class, ['user' => $user])->call('edit')->assertDontSee($apiRole->name)
            ->call('toggleRole', $apiRole->id)->assertNotFound();
        Livewire::test(UserRoles::class, ['user' => $user])->call('edit')->call('toggleRole', 999999)->assertNotFound();
        $this->assertFalse($user->fresh()->hasRole(Roles::ADMIN->value));
    }

    public function test_role_assignment_rechecks_admin_access_after_mounting(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->customer()->create();
        $component = Livewire::actingAs($admin)->test(UserRoles::class, ['user' => $user])->call('edit')->call('toggleRole', Roles::ADMIN->value);
        $admin->removeRole(Roles::ADMIN->value);

        $component->call('save')->assertForbidden();
        $this->assertFalse($user->fresh()->hasRole(Roles::ADMIN->value));
    }

    public function test_last_admin_removal_rejects_all_pending_role_changes(): void
    {
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(UserRoles::class, ['user' => $admin])->call('edit')
            ->call('toggleRole', Roles::CUSTOMER->value)->call('toggleRole', Roles::ADMIN->value)
            ->call('save')->assertHasErrors('roleIds')->assertSet('editing', true);
        $this->assertTrue($admin->fresh()->hasRole(Roles::ADMIN->value));
        $this->assertFalse($admin->fresh()->hasRole(Roles::CUSTOMER->value));
    }

    public function test_admin_role_can_be_removed_if_another_admin_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(UserRoles::class, ['user' => $other])->call('edit')
            ->call('toggleRole', Roles::ADMIN->value)->call('save')->assertHasNoErrors();
        $this->assertFalse($other->fresh()->hasRole(Roles::ADMIN->value));
    }

    public function test_admin_can_remove_own_role_without_leaving_the_page_in_a_forbidden_state(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(UserRoles::class, ['user' => $admin])->call('edit')
            ->call('toggleRole', Roles::ADMIN->value)->call('save')->assertHasNoErrors()->assertRedirect(route('dashboard'));
        $this->assertFalse($admin->fresh()->hasRole(Roles::ADMIN->value));
    }

    public function test_selected_roles_sort_first_with_pending_removals_before_unassigned_roles(): void
    {
        $user = User::factory()->customer()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(UserRoles::class, ['user' => $user])
            ->call('edit')->assertViewHas('roles', fn ($roles): bool => $roles->first()->id === Roles::CUSTOMER->value);
        $component->call('toggleRole', Roles::STAKEHOLDER->value)
            ->assertViewHas('roles', fn ($roles): bool => $roles->take(2)->pluck('id')->all() === [Roles::STAKEHOLDER->value, Roles::CUSTOMER->value]);
        $component->call('toggleRole', Roles::CUSTOMER->value)
            ->assertViewHas('roles', fn ($roles): bool => $roles->take(2)->pluck('id')->all() === [Roles::STAKEHOLDER->value, Roles::CUSTOMER->value]);
    }

    public function test_action_rejects_cross_guard_roles_without_partially_changing_assignments(): void
    {
        $user = User::factory()->customer()->create();
        $apiRole = Role::create(['name' => 'API role', 'guard_name' => 'api']);
        $this->actingAs(User::factory()->admin()->create());

        try {
            app(UpdateUserRoles::class)->handle($user, [Roles::ADMIN->value, $apiRole->id]);
            $this->fail('Expected invalid roles to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('roleIds', $exception->errors());
        }

        $this->assertTrue($user->fresh()->hasRole(Roles::CUSTOMER->value));
        $this->assertFalse($user->fresh()->hasRole(Roles::ADMIN->value));
    }
}
