<?php

namespace Tests\Feature\Customers;

use App\Enums\Roles;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class CustomerRecordsTest extends FeatureTestCase
{
    public static function viewers(): array
    {
        return [
            'admin' => [Roles::ADMIN],
            'stakeholder' => [Roles::STAKEHOLDER],
            'owner' => [Roles::CUSTOMER],
        ];
    }

    #[DataProvider('viewers')]
    public function test_allowed_viewers_see_only_the_customers_active_records(Roles $role): void
    {
        $customer = User::factory()->customer()->create();
        $viewer = $customer;
        if ($role !== Roles::CUSTOMER) {
            $viewer = User::factory()->create();
            $viewer->assignRole($role->value);
        }
        $quote = Quote::factory()->for($customer)->create(['boiler_type' => 'Customer boiler']);
        $work = Work::factory()->for($customer)->create(['order_id' => 'CUSTOMER-WORK']);
        $otherQuote = Quote::factory()->create(['boiler_type' => 'Other boiler']);
        $otherWork = Work::factory()->create(['order_id' => 'OTHER-WORK']);
        $deletedQuote = Quote::factory()->for($customer)->create(['boiler_type' => 'Archived boiler']);
        $deletedWork = Work::factory()->for($customer)->create(['order_id' => 'ARCHIVED-WORK']);
        $deletedQuote->delete();
        $deletedWork->delete();

        $this->actingAs($viewer)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Poptávky zákazníka')->assertSee('Zakázky zákazníka')
            ->assertSee($quote->boiler_type)->assertSee($work->order_id)
            ->assertSee(route('quotes.show', $quote), false)
            ->assertDontSee($otherQuote->boiler_type)->assertDontSee($otherWork->order_id)
            ->assertDontSee($deletedQuote->boiler_type)->assertDontSee($deletedWork->order_id);
    }

    public function test_other_customers_and_users_without_roles_cannot_view_customer_records(): void
    {
        $customer = User::factory()->customer()->create();
        $quote = Quote::factory()->for($customer)->create(['boiler_type' => 'Private boiler']);
        $work = Work::factory()->for($customer)->create(['order_id' => 'PRIVATE-WORK']);

        foreach ([User::factory()->customer()->create(), User::factory()->create()] as $viewer) {
            $this->actingAs($viewer)->get(route('customers.show', $customer))
                ->assertForbidden()->assertDontSee($quote->boiler_type)->assertDontSee($work->order_id);
        }
    }

    public function test_customer_without_records_sees_empty_states(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('customers.show', $customer))
            ->assertOk()->assertSee('Žádné poptávky.')->assertSee('Žádné zakázky.');
    }

    public function test_quote_and_work_pagination_are_independent_and_scoped_to_customer(): void
    {
        $customer = User::factory()->customer()->create();
        $quotes = Quote::factory()->for($customer)->count(11)->create();
        $works = Work::factory()->for($customer)->count(11)->create();

        $response = $this->actingAs($customer)->get(route('customers.show', [
            'customer' => $customer, 'quotes_page' => 2,
        ]))->assertOk();

        $this->assertSame([$quotes->first()->id], $response->viewData('quotes')->pluck('id')->all());
        $this->assertSame($works->reverse()->take(10)->pluck('id')->values()->all(), $response->viewData('works')->pluck('id')->all());

        $response = $this->get(route('customers.show', [
            'customer' => $customer, 'works_page' => 2,
        ]))->assertOk();

        $this->assertSame([$works->first()->id], $response->viewData('works')->pluck('id')->all());
        $this->assertSame($quotes->reverse()->take(10)->pluck('id')->values()->all(), $response->viewData('quotes')->pluck('id')->all());
    }
}
