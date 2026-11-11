<?php

namespace Tests\Feature\Customers;

use App\Enums\Roles;
use App\Livewire\Issues\IssuesTable;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
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
    public function test_allowed_viewers_see_only_the_customers_active_issues(Roles $role): void
    {
        $customer = User::factory()->customer()->create();
        $viewer = $customer;
        if ($role !== Roles::CUSTOMER) {
            $viewer = User::factory()->create();
            $viewer->assignRole($role->value);
        }
        $issue = Issue::factory()->for($customer)->create();
        $closed = Issue::factory()->for($customer)->closed()->create();
        $other = Issue::factory()->create();
        $archived = Issue::factory()->for($customer)->trashed()->create();
        $quote = Quote::factory()->for($issue)->create(['boiler_type' => 'Customer boiler']);
        $work = Work::factory()->forCustomer($customer)->for($issue)->create(['order_id' => 'CUSTOMER-WORK']);

        $response = $this->actingAs($viewer)->get(route('customers.show', $customer));

        $response->assertSee('Případy zákazníka')
            ->assertSee('Případ #' . $issue->id)->assertSee('Případ #' . $closed->id)
            ->assertDontSee('Případ #' . $other->id)->assertDontSee('Případ #' . $archived->id)
            ->assertDontSee('Poptávky zákazníka')->assertDontSee('Zakázky zákazníka')
            ->assertDontSee($quote->boiler_type)->assertDontSee($work->order_id);
        $response->assertSeeLivewire(IssuesTable::class);
        $component = Livewire::actingAs($viewer)->test(IssuesTable::class, ['customer' => $customer]);
        $this->assertSame([$closed->id, $issue->id], $component->get('issues')->modelKeys());
    }

    public function test_other_customers_and_users_without_roles_cannot_view_customer_issues(): void
    {
        $customer = User::factory()->customer()->create();
        $issue = Issue::factory()->for($customer)->create();
        foreach ([User::factory()->customer()->create(), User::factory()->create()] as $viewer) {
            $this->actingAs($viewer)->get(route('customers.show', $customer))
                ->assertForbidden()->assertDontSee('Případ #' . $issue->id);
        }
    }

    public function test_customer_without_issues_sees_empty_state(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->get(route('customers.show', $customer))
            ->assertSee('Žádné případy.');
    }

    public function test_issue_pagination_is_scoped_to_customer(): void
    {
        $customer = User::factory()->customer()->create();
        $issues = Issue::factory()->for($customer)->count(11)->create();
        Issue::factory()->count(11)->create();

        $this->actingAs($customer)->get(route('customers.show', $customer))
            ->assertSeeLivewire(IssuesTable::class);
        $component = Livewire::actingAs($customer)->test(IssuesTable::class, ['customer' => $customer])
            ->call('setPage', 2, 'issues_page');

        $this->assertSame([$issues->first()->id], $component->get('issues')->modelKeys());
        $this->assertSame(11, $component->get('issues')->total());
        $component->call('setPage', 1, 'issues_page');
        $this->assertSame($issues->reverse()->take(10)->values()->modelKeys(), $component->get('issues')->modelKeys());
    }

    public function test_customer_scope_stays_applied_when_searching_and_changing_status_filters(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Matching customer']);
        $own = Issue::factory()->for($customer)->closed()->create();
        $other = Issue::factory()->closed()->create();
        Livewire::actingAs($customer)->test(IssuesTable::class, ['customer' => $customer])
            ->call('setPage', 2, 'issues_page')->set('search', 'Matching')
            ->set('selectedStatuses', ['closed'])
            ->assertSee('Případ #' . $own->id)->assertDontSee('Případ #' . $other->id);
    }

    public function test_direct_component_mount_cannot_expose_another_customer(): void
    {
        $customer = User::factory()->customer()->create();
        Livewire::actingAs(User::factory()->customer()->create())
            ->test(IssuesTable::class, ['customer' => $customer])->assertForbidden();
    }

    public function test_table_rechecks_customer_access_on_livewire_updates(): void
    {
        $viewer = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $component = Livewire::actingAs($viewer)->test(IssuesTable::class, ['customer' => $customer]);
        $viewer->syncRoles([]);

        $component->set('search', 'Changed')->assertForbidden();
    }

    public function test_customer_scope_cannot_be_changed_from_the_browser(): void
    {
        $viewer = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $component = Livewire::actingAs($viewer)->test(IssuesTable::class, ['customer' => $customer]);
        $this->expectException(CannotUpdateLockedPropertyException::class);

        $component->set('customer', User::factory()->customer()->create());
    }
}
