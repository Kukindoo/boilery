<?php

namespace Tests\Feature\Users;

use App\Enums\Roles;
use App\Livewire\Users\UsersTable;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Feature\FeatureTestCase;

class UsersPagesTest extends FeatureTestCase
{
    public static function staffRoles(): array
    {
        return ['admin' => [Roles::ADMIN], 'stakeholder' => [Roles::STAKEHOLDER]];
    }

    public static function invalidSorts(): array
    {
        return ['column' => ['sortBy', 'password'], 'direction' => ['sortDirection', 'invalid']];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_open_users_index_and_see_navigation(Roles $role): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole($role->value);

        $this->actingAs($viewer)->get(route('users.index'))
            ->assertOk()->assertSeeLivewire(UsersTable::class)->assertSee('Uživatelé');
        $this->get(route('settings.profile'))->assertSee(route('users.index'));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_view_all_permission_cannot_access_index_or_component(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->from(route('home'))->get(route('users.index'))
            ->assertStatus(303)
            ->assertRedirect(route('home'))
            ->assertSessionHas('access_denied', true);
        $this->get(route('settings.profile'))->assertDontSee(route('users.index'));
        Livewire::test(UsersTable::class)->assertForbidden();
    }

    public function test_index_lists_every_user_including_staff_customers_and_users_without_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $multipleRoles = User::factory()->admin()->customer()->create();
        $withoutRole = User::factory()->create();

        $component = Livewire::actingAs($admin)->test(UsersTable::class)
            ->assertSee($customer->email)->assertSee($multipleRoles->email)
            ->assertSee($withoutRole->email)->assertSee($admin->email);

        $ids = $component->instance()->users->pluck('id')->all();
        foreach ([$admin, $customer, $multipleRoles, $withoutRole] as $user) {
            $this->assertContains($user->id, $ids);
        }
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_component_rechecks_permission_on_updates(): void
    {
        $viewer = User::factory()->admin()->create();
        $component = Livewire::actingAs($viewer)->test(UsersTable::class);
        $viewer->syncRoles([]);

        $component->set('search', 'Changed')->assertForbidden();
    }

    public function test_role_filter_combines_with_search_and_can_be_cleared(): void
    {
        $viewer = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Matching customer']);
        $multipleRoles = User::factory()->admin()->customer()->create(['name' => 'Matching staff']);
        $withoutRole = User::factory()->create(['name' => 'Matching without role']);
        User::factory()->customer()->create(['name' => 'Other customer']);

        $component = Livewire::actingAs($viewer)->test(UsersTable::class)
            ->set('search', 'Matching')->set('selectedRoles', [(string) Roles::CUSTOMER->value]);

        $this->assertEqualsCanonicalizing(
            [$customer->id, $multipleRoles->id], $component->get('users')->modelKeys(),
        );

        $component->set('selectedRoles', [(string) Roles::CUSTOMER->value, (string) Roles::ADMIN->value, 'none']);
        $this->assertEqualsCanonicalizing(
            [$customer->id, $multipleRoles->id, $withoutRole->id], $component->get('users')->modelKeys(),
        );
    }

    public function test_role_changes_reset_pagination_and_options_include_custom_web_roles(): void
    {
        $viewer = User::factory()->admin()->create();
        $role = Role::create(['name' => 'Custom web role', 'guard_name' => 'web']);
        Role::create(['name' => 'API only role', 'guard_name' => 'api']);
        $user = User::factory()->create();
        $user->assignRole($role);
        User::factory()->count(15)->create();

        $component = Livewire::actingAs($viewer)->test(UsersTable::class)
            ->assertSet('selectedRoles', Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('id')->map(fn ($id): string => (string) $id)->push('none')->all())
            ->assertSee('Custom web role')->assertDontSee('API only role')
            ->call('setPage', 2)->set('selectedRoles', [(string) $role->id])
            ->assertSet('paginators.page', 1);

        $this->assertSame([$user->id], $component->get('users')->modelKeys());
        $component->set('selectedRoles', ['999999'])->assertSee('Žádní uživatelé.');
        $component->set('selectedRoles', [])->assertSee('Žádní uživatelé.');
    }

    public function test_user_names_link_to_accessible_customer_details(): void
    {
        $viewer = User::factory()->admin()->create();
        $user = User::factory()->create();

        Livewire::actingAs($viewer)->test(UsersTable::class)
            ->assertSee('href="' . route('customers.show', $user) . '"', false);
        $this->get(route('customers.show', $user))->assertOk()->assertSee($user->email);
    }

    #[DataProvider('invalidSorts')]
    public function test_browser_cannot_supply_unsafe_sort_values(string $property, string $value): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(UsersTable::class)
            ->set($property, $value)->assertStatus(422);
    }

    public function test_user_names_are_escaped(): void
    {
        $viewer = User::factory()->admin()->create(['name' => '<script>alert(1)</script>']);

        Livewire::actingAs($viewer)->test(UsersTable::class)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_search_matches_all_users_across_contact_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $match = User::factory()->create([
            'name' => 'Jan Novák', 'email' => 'jan@example.test',
            'phone' => '+420604123456', 'address' => 'Dlouhá, Praha',
        ]);
        User::factory()->create(['name' => 'Petr', 'address' => 'Brno']);

        $component = Livewire::actingAs($admin)->test(UsersTable::class);

        foreach (['Novák', 'jan@example.test', '604123456', 'Dlouhá'] as $search) {
            $component->set('search', $search);
            $this->assertSame([$match->id], $component->instance()->users->pluck('id')->all());
        }

        $component->set('search', 'no matching customer')->assertSee('Žádní uživatelé.');
    }

    public function test_search_and_sort_reset_pagination_and_sort_columns_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(16)->create();

        $component = Livewire::actingAs($admin)->test(UsersTable::class)
            ->call('setPage', 2);
        $this->assertSame(2, $component->instance()->users->currentPage());

        $component->set('search', 'no match');
        $this->assertSame(1, $component->instance()->users->currentPage());

        $component->set('search', '')->call('setPage', 2)->call('sort', 'email');
        $this->assertSame(1, $component->instance()->users->currentPage());
        $component->assertSet('sortBy', 'email')->assertSet('sortDirection', 'asc')
            ->call('sort', 'email')->assertSet('sortDirection', 'desc');
        $component->call('sort', 'password')->assertStatus(422);
    }
}
