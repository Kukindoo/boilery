<?php

namespace Tests\Feature\Customers;

use App\Enums\Permissions;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class CustomerNameLinksTest extends FeatureTestCase
{
    public static function pages(): array
    {
        return ['quote' => ['quotes'], 'work' => ['works']];
    }

    #[DataProvider('pages')]
    public function test_customer_names_link_to_customer_when_authorized(string $resource): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo([
            Permissions::QUOTE_VIEW_ALL->label(),
            Permissions::WORK_VIEW_ALL->label(),
            Permissions::USER_VIEW_ALL->label(),
        ]);
        $customer = User::factory()->customer()->create();
        $record = $resource === 'quotes'
            ? Quote::factory()->for($customer, 'user')->create()
            : Work::factory()->for($customer, 'user')->create();
        $href = 'href="' . route('customers.show', $customer) . '"';

        $response = $this->actingAs($viewer)->get(route($resource . '.show', $record))->assertOk();

        $this->assertSame(2, mb_substr_count($response->getContent(), $href));
        $this->get(route('customers.show', $customer))->assertOk();
    }

    #[DataProvider('pages')]
    public function test_customer_names_stay_plain_text_without_customer_view_permission(string $resource): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo([Permissions::QUOTE_VIEW_ALL->label(), Permissions::WORK_VIEW_ALL->label()]);
        $customer = User::factory()->customer()->create();
        $record = $resource === 'quotes'
            ? Quote::factory()->for($customer, 'user')->create()
            : Work::factory()->for($customer, 'user')->create();
        $name = $customer->name;

        $this->actingAs($viewer)->get(route($resource . '.show', $record))
            ->assertOk()->assertSeeText($name)
            ->assertDontSee('href="' . route('customers.show', $customer) . '"', false);
    }

    public function test_quote_edit_permission_does_not_grant_customer_edit_link(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo([Permissions::QUOTE_VIEW_ALL, Permissions::QUOTE_EDIT_ALL]);
        $customer = User::factory()->customer()->create();
        $quote = Quote::factory()->for($customer)->create();
        $href = 'href="' . route('customers.edit', $customer) . '"';

        $this->actingAs($viewer)->get(route('quotes.show', $quote))
            ->assertOk()->assertDontSee($href, false);

        $viewer->givePermissionTo(Permissions::USER_EDIT_ALL);

        $this->get(route('quotes.show', $quote))->assertOk()->assertSee($href, false);
    }
}
