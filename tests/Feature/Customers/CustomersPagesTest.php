<?php

namespace Tests\Feature\Customers;

use App\Enums\Roles;
use App\Livewire\Customers\CustomerEdit;
use App\Livewire\Customers\CustomersTable;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class CustomersPagesTest extends FeatureTestCase
{
    public static function staffRoles(): array
    {
        return ['admin' => [Roles::ADMIN], 'stakeholder' => [Roles::STAKEHOLDER]];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_list_show_and_edit_customers(Roles $role): void
    {
        $staff = User::factory()->create();
        $staff->assignRole($role->value);
        $customer = User::factory()->customer()->create(['address' => 'Praha']);

        $this->actingAs($staff)->get(route('customers.index'))
            ->assertOk()->assertSeeLivewire(CustomersTable::class)
            ->assertSee($customer->name)->assertSee('Zákazník');
        $this->get(route('customers.show', $customer))
            ->assertOk()->assertSee($customer->email)->assertSee($customer->phone)->assertSee('Praha');
        $this->get(route('customers.edit', $customer))
            ->assertOk()->assertSeeLivewire(CustomerEdit::class);
    }

    public function test_customer_pages_require_authentication(): void
    {
        $customer = User::factory()->customer()->create();

        foreach (['customers.index', 'customers.show', 'customers.edit'] as $route) {
            $this->get(route($route, $route === 'customers.index' ? [] : $customer))
                ->assertRedirect(route('login'));
        }
    }

    public function test_customer_role_and_users_without_roles_cannot_access_the_section(): void
    {
        $customer = User::factory()->customer()->create();

        $otherCustomer = User::factory()->customer()->create();

        foreach ([$customer, User::factory()->create()] as $user) {
            $this->actingAs($user);

            foreach (['customers.index', 'customers.show', 'customers.edit'] as $route) {
                $this->get(route($route, $route === 'customers.index' ? [] : $otherCustomer))
                    ->assertForbidden();
            }

            $this->get(route('settings.profile'))->assertDontSee(route('customers.index'));
            Livewire::test(CustomersTable::class)->assertForbidden();
            Livewire::test(CustomerEdit::class, ['customer' => $otherCustomer])->assertForbidden();
        }
    }

    public function test_all_user_permissions_also_allow_access_to_users_without_customer_role(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $nonCustomer = User::factory()->create();

        $this->get(route('customers.show', $nonCustomer))->assertOk();
        $this->get(route('customers.edit', $nonCustomer))->assertOk();
        Livewire::test(CustomerEdit::class, ['customer' => $nonCustomer])->assertOk();
        $this->get(route('customers.show', 999999))->assertNotFound();
    }

    public function test_table_lists_only_customer_role_including_users_with_multiple_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $multipleRoles = User::factory()->admin()->customer()->create();
        $nonCustomer = User::factory()->create();

        $component = Livewire::actingAs($admin)->test(CustomersTable::class)
            ->assertSee($customer->email)->assertSee($multipleRoles->email)
            ->assertDontSee($nonCustomer->email);

        $this->assertEqualsCanonicalizing(
            [$customer->id, $multipleRoles->id],
            $component->instance()->customers->pluck('id')->all(),
        );
    }

    public function test_search_is_scoped_to_customers_across_contact_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $match = User::factory()->customer()->create([
            'name' => 'Jan Novák', 'email' => 'jan@example.test',
            'phone' => '+420604123456', 'address' => 'Dlouhá, Praha',
        ]);
        User::factory()->create(['name' => 'Jan Novák', 'address' => 'Dlouhá, Praha']);
        User::factory()->customer()->create(['name' => 'Petr', 'address' => 'Brno']);

        $component = Livewire::actingAs($admin)->test(CustomersTable::class);

        foreach (['Novák', 'jan@example.test', '604123456', 'Dlouhá'] as $search) {
            $component->set('search', $search);
            $this->assertSame([$match->id], $component->instance()->customers->pluck('id')->all());
        }

        $component->set('search', 'no matching customer')->assertSee('Žádní zákazníci.');
    }

    public function test_search_and_sort_reset_pagination_and_sort_columns_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->customer()->count(16)->create();

        $component = Livewire::actingAs($admin)->test(CustomersTable::class)
            ->call('setPage', 2);
        $this->assertSame(2, $component->instance()->customers->currentPage());

        $component->set('search', 'no match');
        $this->assertSame(1, $component->instance()->customers->currentPage());

        $component->set('search', '')->call('setPage', 2)->call('sort', 'email');
        $this->assertSame(1, $component->instance()->customers->currentPage());
        $component->assertSet('sortBy', 'email')->assertSet('sortDirection', 'asc')
            ->call('sort', 'email')->assertSet('sortDirection', 'desc');
        $component->call('sort', 'password')->assertStatus(422);
    }
}
