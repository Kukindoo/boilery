<?php

namespace Tests\Feature\Quote;

use App\Enums\Permissions;
use App\Livewire\Quotes\CustomerQuotesTable;
use App\Models\Quote;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class CustomerQuotesTableTest extends FeatureTestCase
{
    public static function searchableFields(): array
    {
        return [
            'boiler type' => ['boiler_type'],
            'serial number' => ['boiler_serial_number'],
            'message' => ['message'],
        ];
    }

    public function test_dashboard_lists_only_the_customers_quotes_and_links_to_details(): void
    {
        $customer = User::factory()->customer()->create();
        $quote = Quote::factory()->forCustomer($customer)->accepted()->create(['boiler_type' => 'Owned boiler']);
        Quote::factory()->create(['boiler_type' => 'Other customer boiler']);
        Quote::factory()->forCustomer($customer)->trashed()->create(['boiler_type' => 'Deleted boiler']);

        $this->actingAs($customer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Owned boiler')
            ->assertSee(route('quotes.show', $quote, absolute: false))
            ->assertDontSee('Other customer boiler')
            ->assertDontSee('Deleted boiler');
    }

    #[DataProvider('searchableFields')]
    public function test_search_filters_owned_quotes_without_exposing_other_customers_quotes(string $field): void
    {
        $customer = User::factory()->customer()->create();
        Quote::factory()->forCustomer($customer)->create([$field => 'Search needle', 'boiler_type' => $field === 'boiler_type' ? 'Search needle' : 'Matching boiler']);
        Quote::factory()->forCustomer($customer)->create(['boiler_type' => 'Unmatched boiler']);
        Quote::factory()->create([$field => 'Search needle', 'boiler_type' => 'Private Search needle']);

        Livewire::actingAs($customer)->test(CustomerQuotesTable::class)
            ->set('search', 'Search needle')
            ->assertSee($field === 'boiler_type' ? 'Search needle' : 'Matching boiler')
            ->assertDontSee('Unmatched boiler')
            ->assertDontSee('Private Search needle');
    }

    public function test_search_by_quote_number_resets_pagination_and_can_be_cleared(): void
    {
        $customer = User::factory()->customer()->create();
        $quote = Quote::factory()->forCustomer($customer)->create(['boiler_type' => 'Oldest boiler']);
        Quote::factory()->forCustomer($customer)->count(10)->create(['boiler_type' => 'Recent boiler']);

        Livewire::actingAs($customer)->test(CustomerQuotesTable::class)
            ->assertDontSee('Oldest boiler')
            ->call('setPage', 2)
            ->assertSee('Oldest boiler')
            ->set('search', '#' . $quote->id)
            ->assertSet('paginators.page', 1)
            ->assertSee('Oldest boiler')
            ->assertDontSee('Recent boiler')
            ->set('search', 'no matching quotes')
            ->assertSee('Žádné poptávky neodpovídají hledání.')
            ->set('search', '')
            ->assertSee('Recent boiler');
    }

    public function test_customer_without_quotes_sees_an_empty_state(): void
    {
        Livewire::actingAs(User::factory()->customer()->create())->test(CustomerQuotesTable::class)
            ->assertSee('Zatím nemáte žádné poptávky.');
    }

    public function test_component_rechecks_permission_on_search(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::QUOTE_VIEW_OWN);
        $component = Livewire::actingAs($user)->test(CustomerQuotesTable::class);
        $user->revokePermissionTo(Permissions::QUOTE_VIEW_OWN);

        $component->set('search', 'boiler')->assertForbidden();
    }

    public function test_component_requires_permission(): void
    {
        Livewire::actingAs(User::factory()->create())->test(CustomerQuotesTable::class)->assertForbidden();
    }

    public function test_customer_can_read_own_quote_details_but_cannot_read_another_customers_quote(): void
    {
        $customer = User::factory()->customer()->create();
        $quote = Quote::factory()->forCustomer($customer)->create(['message' => 'My boiler needs repair']);
        $otherQuote = Quote::factory()->create();

        $this->actingAs($customer)->get(route('quotes.show', $quote))
            ->assertOk()
            ->assertSee('My boiler needs repair');
        $this->get(route('quotes.show', $otherQuote))->assertForbidden();
    }
}
