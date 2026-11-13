<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Enums\Roles;
use App\Enums\WorkStatus;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use PHPUnit\Framework\Attributes\DataProvider;

class DashboardTest extends FeatureTestCase
{
    /** @return array<string, array{Roles, bool}> */
    public static function staffRoles(): array
    {
        return [
            'admin' => [Roles::ADMIN, false],
            'stakeholder' => [Roles::STAKEHOLDER, false],
            'admin and customer' => [Roles::ADMIN, true],
            'stakeholder and customer' => [Roles::STAKEHOLDER, true],
        ];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_dashboards_show_statistics_and_controls_without_record_tables(Roles $role, bool $alsoCustomer): void
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        if ($alsoCustomer) {
            $user->assignRole(Roles::CUSTOMER);
        }
        $open = Issue::factory()->create();
        Quote::factory()->forCustomer($user)->create(['boiler_type' => 'Dashboard quote boiler']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Případ #' . $open->id)
            ->assertDontSee('Hledat případy')
            ->assertDontSee('Dashboard quote boiler')
            ->assertDontSee('Moje poptávky')
            ->assertDontSee('Send Cold Email Test');
    }

    public function test_statistics_count_each_status_exclude_archived_records_and_link_to_indexes(): void
    {
        $admin = User::factory()->admin()->create();
        $open = Issue::factory()->create();
        Issue::factory()->closed()->count(2)->create();
        Issue::factory()->trashed()->create();
        Quote::factory()->for($open)->count(2)->create(['status' => QuoteStatus::NEW]);
        Quote::factory()->for($open)->create(['status' => QuoteStatus::PROCESSING]);
        Quote::factory()->for($open)->create(['status' => QuoteStatus::ACCEPTED]);
        Quote::factory()->for($open)->trashed()->create(['status' => QuoteStatus::NEW]);
        Work::factory()->for($open)->create(['status' => WorkStatus::NEW]);
        Work::factory()->for($open)->count(2)->create(['status' => WorkStatus::PROCESSING]);
        Work::factory()->for($open)->create(['status' => WorkStatus::FINISHED]);
        Work::factory()->for($open)->create(['status' => WorkStatus::CANCELLED]);
        Work::factory()->for($open)->trashed()->create(['status' => WorkStatus::PROCESSING]);

        $response = $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $statistics = $response->viewData('statistics');
        $this->assertSame(4, $statistics['quotes']['total']);
        $this->assertSame(['new' => 2, 'contacted' => 0, 'processing' => 1, 'accepted' => 1, 'rejected' => 0],
            array_map(fn (array $status): int => $status['count'], $statistics['quotes']['statuses']));
        $this->assertSame(5, $statistics['works']['total']);
        $this->assertSame(['new' => 1, 'processing' => 2, 'finished' => 1, 'cancelled' => 1],
            array_map(fn (array $status): int => $status['count'], $statistics['works']['statuses']));
        $this->assertSame(3, $statistics['issues']['total']);
        $this->assertSame(['open' => 1, 'closed' => 2],
            array_map(fn (array $status): int => $status['count'], $statistics['issues']['statuses']));
        foreach (['quotes' => 'Poptávky', 'works' => 'Zakázky', 'issues' => 'Případy'] as $key => $heading) {
            $response->assertSee('Zobrazit vše – ' . $heading)->assertSee(route($key . '.index'));
        }
    }

    public function test_empty_statistics_show_zero_totals_and_status_counts(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('dashboard'))->assertOk();

        foreach ($response->viewData('statistics') as $statistic) {
            $this->assertSame(0, $statistic['total']);
            foreach ($statistic['statuses'] as $status) {
                $this->assertSame(0, $status['count']);
            }
        }
    }

    public function test_statistics_do_not_expose_counts_or_links_without_index_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::STAKEHOLDER);
        Work::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Nemáte oprávnění k zobrazení statistik.')
            ->assertDontSee('Zobrazit vše – Zakázky');

        $statistics = $response->viewData('statistics');
        $this->assertFalse($statistics['works']['canView']);
        $this->assertNull($statistics['works']['total']);
        $this->assertSame([], $statistics['works']['statuses']);
    }

    public function test_customer_dashboard_lists_owned_quotes_instead_of_issues(): void
    {
        $customer = User::factory()->customer()->create();
        Quote::factory()->forCustomer($customer)->create(['boiler_type' => 'Owned dashboard boiler']);
        Quote::factory()->create(['boiler_type' => 'Other dashboard boiler']);
        $issue = Issue::factory()->for($customer)->create();

        $this->actingAs($customer)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('statistics', [])
            ->assertSee('Owned dashboard boiler')
            ->assertDontSee('Other dashboard boiler')
            ->assertDontSee('Případ #' . $issue->id)
            ->assertDontSee('Send Cold Email Test');
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertStatus(200);
    }
}
