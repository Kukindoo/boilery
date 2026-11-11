<?php

namespace Tests\Feature;

use App\Actions\Quotes\AcceptQuote;
use App\Livewire\Issues\IssueEdit;
use App\Livewire\Works\WorkCreate;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Throwable;

class WorkIssueTest extends FeatureTestCase
{
    public function test_manual_creation_uses_the_selected_parent_issue(): void
    {
        $user = User::factory()->admin()->create();
        $issue = Issue::factory()->for($user)->create();
        Livewire::actingAs($user)->test(WorkCreate::class)->set('form.issueId', $issue->id)
            ->set('form.orderId', 'WORK-WITH-ISSUE')->call('submit')->assertHasNoErrors();
        $work = Work::query()->sole();
        $this->assertSame($user->id, $work->issue->user_id);
        $this->assertSame(1, Issue::query()->count());
    }

    /** @throws Throwable */
    public function test_quote_acceptance_creates_or_reuses_the_quotes_parent_issue(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $quote = Quote::factory()->create();
        app(AcceptQuote::class)->handle($quote);
        $this->assertSame($quote->fresh()->issue_id, $quote->issue->works()->sole()->issue_id);
        $this->assertNotNull($quote->fresh()->issue_id);
        $issue = Issue::factory()->create();
        $linked = Quote::factory()->for($issue)->create();
        app(AcceptQuote::class)->handle($linked);
        $this->assertSame($issue->id, $linked->issue->works()->sole()->issue_id);
        $this->assertSame(2, Issue::query()->count());
    }

    public function test_manual_creation_uses_an_issue_without_linking_its_quote(): void
    {
        $user = User::factory()->admin()->create();
        $issue = Issue::factory()->for($user)->create();
        $quote = Quote::factory()->forCustomer($user)->for($issue)->create();
        Livewire::actingAs($user)->test(WorkCreate::class)->set('form.issueId', $issue->id)->set('form.orderId', 'LINKED-WORK')
            ->assertDontSee('form.quoteId', false)->call('submit')->assertHasNoErrors();
        $this->assertNull(Work::query()->sole()->quote_id);
        $this->assertSame($issue->id, Work::query()->sole()->issue_id);
        $this->assertSame(1, Issue::query()->count());
    }

    public function test_editing_an_issue_preserves_its_works(): void
    {
        $user = User::factory()->admin()->create();
        $work = Work::factory()->create();
        Livewire::actingAs($user)->test(IssueEdit::class, ['issue' => $work->issue])
            ->call('submit')->assertHasNoErrors();
        $this->assertNotNull($work->fresh()->issue_id);
    }

    public function test_database_rejects_a_work_without_a_parent_issue(): void
    {
        $work = Work::factory()->create();
        $this->expectException(QueryException::class);
        DB::table('works')->where('id', $work->id)->update(['issue_id' => null]);
    }

    public function test_database_rejects_deleting_an_issue_with_archived_works(): void
    {
        $work = Work::factory()->trashed()->create();
        $this->expectException(QueryException::class);
        $work->issue->forceDelete();
    }
}
