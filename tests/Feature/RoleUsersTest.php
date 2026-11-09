<?php

namespace Tests\Feature;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Modals\Deletion;
use App\Livewire\Roles\RoleUsers;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Role;

class RoleUsersTest extends FeatureTestCase
{
    public function test_role_show_lists_only_assigned_users_and_links_to_their_details(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $assigned = User::factory()->create();
        $assigned->assignRole($role);
        $other = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('roles.show', $role))
            ->assertSeeLivewire(RoleUsers::class)->assertSee($assigned->email)->assertDontSee($other->email);
        Livewire::test(RoleUsers::class, ['role' => $role])->assertSeeHtml('href="' . route('customers.show', $assigned) . '"');
    }

    #[TestWith(['name'])]
    #[TestWith(['email'])]
    public function test_search_matches_user_fields_without_including_users_from_another_role(string $field): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $value = $field === 'email' ? 'match@example.test' : 'Matching value';
        $matching = User::factory()->create([$field => $value]);
        $matching->assignRole($role);
        $notMatching = User::factory()->create();
        $notMatching->assignRole($role);
        User::factory()->create([$field => $field === 'email' ? 'other@example.test' : $value]);

        $component = Livewire::actingAs(User::factory()->admin()->create())->test(RoleUsers::class, ['role' => $role])
            ->set('search', $field === 'email' ? '@example.test' : $value);

        $this->assertSame([$matching->id], $component->instance()->users()->pluck('id')->all());
        $component->set('search', 'No results here')->assertSee('Žádní uživatelé neodpovídají filtru.');
    }

    public function test_search_resets_pagination_and_refresh_reflects_removed_assignments(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $users = User::factory()->count(16)->create();
        foreach ($users as $user) {
            $user->assignRole($role);
        }
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(RoleUsers::class, ['role' => $role]);
        $this->assertSame(15, $component->instance()->users()->count());
        $component->call('setPage', 2, 'role_users_page');
        $this->assertSame(1, $component->instance()->users()->count());
        $matching = $users->first();
        $component->set('search', $matching->email)->assertSet('paginators.role_users_page', 1)->assertSee($matching->email);
        $matching->removeRole($role);
        $component->call('$refresh')->assertSee('Žádní uživatelé neodpovídají filtru.');
    }

    public function test_non_admin_and_other_guard_roles_cannot_be_used(): void
    {
        $role = Role::findById(Roles::CUSTOMER->value);
        Livewire::actingAs(User::factory()->customer()->create())->test(RoleUsers::class, ['role' => $role])->assertForbidden();
        $api = Role::create(['name' => 'API role', 'guard_name' => 'api']);
        Livewire::actingAs(User::factory()->admin()->create())->test(RoleUsers::class, ['role' => $api])->assertForbidden();
    }

    public function test_removal_requires_confirmation_and_preserves_user_and_other_roles(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $user = User::factory()->customer()->create();
        $user->assignRole($role);
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(RoleUsers::class, ['role' => $role])
            ->assertDontSeeLivewire(Deletion::class)->assertSee('Odebrat roli')
            ->assertDontSee('Telefon')->assertDontSee('Adresa')
            ->call('confirmRemoveRole', $user->id)->assertSet('confirmingRemoval', true)->assertSeeLivewire(Deletion::class);
        $this->assertTrue($user->fresh()->hasRole($role));

        $component->call('removeRole')->assertHasNoErrors()->assertSet('confirmingRemoval', false)->assertDontSee($user->email);
        $this->assertFalse($user->fresh()->hasRole($role));
        $this->assertTrue($user->fresh()->hasRole(Roles::CUSTOMER->value));
        $this->assertModelExists($user);
    }

    public function test_fresh_page_does_not_mount_the_deletion_modal(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(RoleUsers::class, ['role' => $role])
            ->assertSet('confirmingRemoval', false)->assertSet('removeUserId', null)
            ->assertDontSeeLivewire(Deletion::class)->call('confirmRemoveRole', $user->id)->assertSeeLivewire(Deletion::class);

        Livewire::test(RoleUsers::class, ['role' => $role])
            ->assertSet('confirmingRemoval', false)->assertSet('removeUserId', null)->assertDontSeeLivewire(Deletion::class);
        $this->get(route('roles.show', $role))->assertDontSeeLivewire(Deletion::class);
    }

    public function test_cancelling_confirmation_does_not_remove_the_role(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        Livewire::actingAs(User::factory()->admin()->create())->test(RoleUsers::class, ['role' => $role])
            ->call('confirmRemoveRole', $user->id)->call('cancelRemoval')
            ->assertSet('confirmingRemoval', false)->assertSet('removeUserId', null)
            ->assertDontSeeLivewire(Deletion::class)->set('search', $user->email)
            ->assertSee($user->email)->assertDontSeeLivewire(Deletion::class);
        $this->assertTrue($user->fresh()->hasRole($role));
    }

    public function test_removal_rejects_unassigned_users_and_rechecks_authorization(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(RoleUsers::class, ['role' => $role])->call('confirmRemoveRole', $other->id)->assertNotFound();
        Livewire::test(RoleUsers::class, ['role' => $role])->call('removeRole')->assertNotFound();
        $other->assignRole($role);
        $component = Livewire::test(RoleUsers::class, ['role' => $role])->call('confirmRemoveRole', $other->id);
        $admin->removeRole(Roles::ADMIN->value);

        $component->call('removeRole')->assertForbidden();
        $this->assertTrue($other->fresh()->hasRole($role));
    }

    public function test_last_admin_cannot_be_removed_from_the_role_list(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::findById(Roles::ADMIN->value);
        Livewire::actingAs($admin)->test(RoleUsers::class, ['role' => $role])
            ->call('confirmRemoveRole', $admin->id)->call('removeRole')->assertHasErrors('roleIds');
        $this->assertTrue($admin->fresh()->hasRole($role));
    }

    public function test_admin_removing_own_role_is_redirected_if_another_admin_exists(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();
        $role = Role::findById(Roles::ADMIN->value);
        Livewire::actingAs($admin)->test(RoleUsers::class, ['role' => $role])
            ->call('confirmRemoveRole', $admin->id)->call('removeRole')->assertRedirect(route('dashboard'));
        $this->assertFalse($admin->fresh()->hasRole($role));
    }

    public function test_user_listing_permission_is_required_and_rechecked_on_search(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $component = Livewire::actingAs($admin)->test(RoleUsers::class, ['role' => $role]);
        Role::findById(Roles::ADMIN->value)->revokePermissionTo(Permissions::USER_VIEW_ALL->label());

        $component->set('search', 'anything')->assertForbidden();
        $this->get(route('roles.show', $role))->assertDontSeeLivewire(RoleUsers::class);
        Livewire::test(RoleUsers::class, ['role' => $role])->assertForbidden();
    }
}
