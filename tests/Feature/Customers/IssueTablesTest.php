<?php

namespace Tests\Feature\Customers;

use App\Livewire\Customers\Tables\QuotesTable;
use App\Livewire\Customers\Tables\WorksTable;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

class IssueTablesTest extends FeatureTestCase
{
    public static function tables(): array
    {
        return [
            'quotes' => [QuotesTable::class, Quote::class, 'boiler_type', 'quotes_page'],
            'works' => [WorksTable::class, Work::class, 'type_of_product', 'works_page'],
        ];
    }

    #[DataProvider('tables')]
    public function test_tables_paginate_only_records_of_the_selected_issue(string $component, string $model, string $field, string $pageName): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $issue = Issue::factory()->create();
        $model::factory()->for($issue)->create([$field => 'Oldest record']);
        $model::factory()->for($issue)->count(10)->create([$field => 'Recent record']);
        $model::factory()->create([$field => 'Other issue record']);

        Livewire::test($component, ['issue' => $issue])
            ->assertSee('Recent record')->assertDontSee('Oldest record')->assertDontSee('Other issue record')
            ->call('setPage', 2, $pageName)
            ->assertSee('Oldest record')->assertDontSee('Recent record')->assertDontSee('Other issue record');
    }

    #[DataProvider('tables')]
    public function test_tables_reject_access_to_another_customers_issue(string $component, string $model, string $field, string $pageName): void
    {
        $this->actingAs(User::factory()->customer()->create());

        Livewire::test($component, ['issue' => Issue::factory()->create()])->assertForbidden();
    }

    public function test_issue_page_renders_both_livewire_tables(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $issue = Issue::factory()->create();
        Quote::factory()->for($issue)->create(['boiler_type' => 'Issue boiler']);
        Work::factory()->for($issue)->create(['order_id' => 'ISSUE-WORK']);

        $this->get(route('issues.show', $issue))
            ->assertSeeLivewire(QuotesTable::class)->assertSeeLivewire(WorksTable::class)
            ->assertSee('Issue boiler')->assertSee('ISSUE-WORK');
    }
}
