<?php

namespace Tests\Feature;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\Modals\Deletion;
use App\Livewire\Permissions\PermissionsTable;
use App\Livewire\Roles\RolePermissions;
use App\Livewire\Roles\RolesTable;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsPagesTest extends FeatureTestCase
{
    #[TestWith(['roles', Role::class])]
    #[TestWith(['permissions', Permission::class])]
    public function test_admin_can_list_and_view_records_without_crud_controls(string $resource, string $model): void
    {
        $record = $model::query()->orderBy('name')->firstOrFail();
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route($resource . '.index'))->assertSee($record->name)
            ->assertDontSee('Nová role')->assertDontSee('Nové oprávnění')->assertDontSeeLivewire(Deletion::class);
        $this->get(route($resource . '.show', $record))->assertSee($record->name)
            ->assertDontSee('href="' . route($resource . '.show', $record) . '/edit"', false);
    }

    #[TestWith(['roles', Role::class])]
    #[TestWith(['permissions', Permission::class])]
    public function test_crud_routes_are_unavailable_even_to_admins(string $resource, string $model): void
    {
        $record = $model::query()->firstOrFail();
        $originalName = $record->name;
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/' . $resource . '/create')->assertNotFound();
        $this->get('/' . $resource . '/' . $record->id . '/edit')->assertNotFound();
        $this->post('/' . $resource, ['name' => 'New record'])->assertMethodNotAllowed();
        $this->put('/' . $resource . '/' . $record->id, ['name' => 'Renamed'])->assertMethodNotAllowed();
        $this->delete('/' . $resource . '/' . $record->id)->assertMethodNotAllowed();
        $this->assertFalse(Gate::allows('create', $model));
        $this->assertFalse(Gate::allows('update', $record));
        $this->assertFalse(Gate::allows('delete', $record));
        $this->assertSame($originalName, $record->refresh()->name);
    }

    #[TestWith(['roles', Role::class])]
    #[TestWith(['permissions', Permission::class])]
    public function test_guests_and_non_admins_cannot_access_pages(string $resource, string $model): void
    {
        $record = $model::query()->firstOrFail();
        $this->get(route($resource . '.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create());

        $this->get(route($resource . '.index'))->assertForbidden();
        $this->get(route($resource . '.show', $record))->assertForbidden();
    }

    #[TestWith([RolesTable::class])]
    #[TestWith([PermissionsTable::class])]
    public function test_stakeholder_cannot_mount_management_tables(string $component): void
    {
        $stakeholder = User::factory()->create();
        $stakeholder->assignRole(Roles::STAKEHOLDER->value);

        Livewire::actingAs($stakeholder)->test($component)->assertForbidden();
    }

    public function test_role_guards_are_restricted_while_permission_view_is_guard_independent(): void
    {
        $role = Role::create(['name' => 'API role', 'guard_name' => 'api']);
        $permission = Permission::create(['name' => 'API permission', 'guard_name' => 'api']);
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('roles.show', $role))->assertForbidden();
        $this->get(route('permissions.show', $permission))->assertOk();
        Livewire::test(RolePermissions::class, ['role' => $role])->assertForbidden();
    }

    public function test_role_detail_keeps_badges_in_model_and_scope_groups(): void
    {
        $role = Role::create(['name' => 'Grouped badges', 'guard_name' => 'web']);
        $role->syncPermissions([Permissions::QUOTE_VIEW_ALL->label(), Permissions::QUOTE_EDIT_OWN->label(), Permissions::WORK_VIEW_ALL->label()]);
        $this->actingAs(User::factory()->admin()->create())->get(route('roles.show', $role))
            ->assertSeeLivewire(RolePermissions::class)->assertSee('data-flux-badge', false);

        Livewire::test(RolePermissions::class, ['role' => $role])
            ->assertViewHas('permissionScopes', fn ($groups): bool => $groups['quotes']['all']->sole()->name === 'View all quotes'
                && $groups['quotes']['own']->sole()->name === 'Edit own quotes'
                && $groups['works']['all']->sole()->name === 'View all works');
    }

    public function test_model_grouping_uses_names_when_database_ids_differ(): void
    {
        Permission::findByName('Edit all users')->delete();
        Permission::findByName('View all assembly actions')->delete();
        $userPermission = Permission::forceCreate(['id' => 10001, 'name' => 'Edit all users', 'guard_name' => 'web']);
        $assemblyPermission = Permission::forceCreate(['id' => 10002, 'name' => 'View all assembly actions', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Grouped role', 'guard_name' => 'web']);
        $role->syncPermissions([$userPermission, $assemblyPermission]);

        Livewire::actingAs(User::factory()->admin()->create())->test(RolePermissions::class, ['role' => $role])
            ->assertViewHas('permissionScopes', fn ($groups): bool => $groups['users']['all']->sole()->id === 10001
                && $groups['assembly actions']['all']->sole()->id === 10002);
    }

    public function test_role_table_counts_users_and_links_names(): void
    {
        $role = Role::create(['name' => 'Technician', 'guard_name' => 'web']);
        $empty = Role::create(['name' => 'Unassigned role', 'guard_name' => 'web']);
        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            $user->assignRole($role);
        }
        User::factory()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(RolesTable::class)->set('search', 'Technician');

        $this->assertMatchesRegularExpression('~<a\b[^>]*href="' . preg_quote(route('roles.show', $role), '~') . '"[^>]*>\s*Technician\s*</a>~', $component->html());
        $this->assertSame(2, $component->instance()->roles()->sole()->users_count);
        $component->set('search', $empty->name);
        $this->assertSame(0, $component->instance()->roles()->sole()->users_count);
        $users->first()->removeRole($role);
        $component->set('search', 'Technician');
        $this->assertSame(1, $component->instance()->roles()->sole()->users_count);
    }

    public function test_permission_table_counts_roles_and_links_names(): void
    {
        $permission = Permission::create(['name' => 'Inspect equipment', 'guard_name' => 'web']);
        Role::findById(Roles::ADMIN->value)->givePermissionTo($permission);
        Role::findById(Roles::STAKEHOLDER->value)->givePermissionTo($permission);

        $component = Livewire::actingAs(User::factory()->admin()->create())->test(PermissionsTable::class)->set('search', $permission->name);
        $this->assertSame(2, $component->instance()->permissions()->sole()->roles_count);
        $this->assertMatchesRegularExpression('~<a\b[^>]*href="' . preg_quote(route('permissions.show', $permission), '~') . '"[^>]*>.*?<span\b[^>]*>\s*Inspect equipment\s*</span>.*?</a>~s', $component->html());
    }

    public function test_permissions_table_groups_complete_models_and_keeps_search_and_sort(): void
    {
        $custom = Permission::create(['name' => 'Special access', 'guard_name' => 'web']);
        $api = Permission::create(['name' => 'API permission', 'guard_name' => 'api']);
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(PermissionsTable::class)
            ->assertSee('Assembly actions')->assertSee('Quotes')->assertSee('Works')->assertSee('Ostatní')
            ->assertSee($custom->name)->assertDontSee($api->name)
            ->assertViewHas('permissionGroups', fn ($groups): bool => $groups['works']->contains('name', Permissions::WORK_VIEW_ALL->label())
                && ! $groups['works']->contains('name', Permissions::QUOTE_VIEW_ALL->label()));
        $this->assertSame(Permission::query()->where('guard_name', 'web')->count(), $component->instance()->permissions()->count());
        $component->set('search', 'all quotes')->assertSee('Quotes')->assertDontSee('Assembly actions')
            ->assertViewHas('permissionGroups', fn ($groups): bool => $groups->keys()->all() === ['quotes']);
        $component->call('sort')->assertSet('sortDirection', 'desc');
        $names = $component->instance()->permissions()->pluck('name')->all();
        $this->assertSame('View all quotes', $names[0]);
        $component->set('search', 'No matching permission')->assertSee('Žádné záznamy.');
    }

    public function test_search_sort_and_escaping_remain_available(): void
    {
        $role = Role::create(['name' => '<script>alert(1)</script>', 'guard_name' => 'web']);

        Livewire::actingAs(User::factory()->admin()->create())->test(RolesTable::class)
            ->set('search', '<script>')->assertSee($role->name)->assertDontSee($role->name, false)
            ->assertDontSee('Stakeholder')->call('sort')->assertSet('sortDirection', 'desc');
    }
}
