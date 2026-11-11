<?php

namespace Tests\Feature;

use App\Actions\Issues\CreateIssueWork;
use App\Enums\Permissions;
use App\Livewire\Works\WorkCreate;
use App\Livewire\Works\WorkEdit;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Throwable;

class WorkOwnershipTest extends FeatureTestCase
{
    /** @throws Throwable */
    public function test_work_and_customer_resolve_each_other_through_issue_without_direct_owner_column(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $customer = User::factory()->create();
        $issue = Issue::factory()->for($customer)->create();
        $work = app(CreateIssueWork::class)->handle($issue);

        $this->assertTrue($work->user->is($customer));
        $this->assertTrue($customer->works()->sole()->is($work));
        $this->assertTrue($work->isFileOwner($customer));
        $this->assertFalse(Schema::hasColumn('works', 'user_id'));
        $this->assertArrayNotHasKey('user_id', $work->getAttributes());
    }

    public function test_issue_owner_change_updates_work_ownership_and_permissions(): void
    {
        $owner = User::factory()->customer()->create();
        $owner->givePermissionTo(Permissions::WORK_EDIT_OWN->label());
        $other = User::factory()->customer()->create();
        $other->givePermissionTo(Permissions::WORK_EDIT_OWN->label());
        $work = Work::factory()->forCustomer($owner)->create();
        $this->assertTrue($owner->can('update', $work));

        $work->issue->update(['user_id' => $other->id]);

        $this->assertFalse($owner->can('update', $work));
        $this->assertTrue($other->can('update', $work));
        $this->assertTrue($work->fresh()->user->is($other));
        $this->assertSame(0, $owner->works()->count());
        $this->assertSame(1, $other->works()->count());
    }

    public function test_archiving_issue_keeps_work_customer_and_file_ownership(): void
    {
        $customer = User::factory()->create();
        $work = Work::factory()->forCustomer($customer)->create();
        $work->issue->delete();

        $this->assertTrue($work->fresh()->user->is($customer));
        $this->assertTrue($work->fresh()->isFileOwner($customer));
    }

    public function test_standalone_work_creation_requires_an_existing_parent_issue(): void
    {
        $admin = User::factory()->admin()->create();
        Livewire::actingAs($admin)->test(WorkCreate::class)
            ->set('form.orderId', 'MISSING-ISSUE')->call('submit')->assertHasErrors('form.issueId')
            ->set('form.issueId', 999999)->call('submit')->assertHasErrors('form.issueId');
        $this->assertDatabaseCount('works', 0);
    }

    public function test_work_cannot_link_a_quote_from_another_issue_on_create_or_update(): void
    {
        $admin = User::factory()->admin()->create();
        $issue = Issue::factory()->create();
        $quote = Quote::factory()->create();
        Livewire::actingAs($admin)->test(WorkCreate::class)
            ->set('form.issueId', $issue->id)->set('form.orderId', 'MISMATCHED-ISSUE')
            ->set('form.quoteId', $quote->id)->call('submit')->assertHasErrors('form.quoteId');
        $this->assertDatabaseCount('works', 0);
        $work = Work::factory()->for($issue)->create();
        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('form.quoteId', $quote->id)->call('submit')->assertHasErrors('form.quoteId');
        $this->assertNull($work->fresh()->quote_id);
        $this->assertSame($issue->id, $work->fresh()->issue_id);
    }
}
