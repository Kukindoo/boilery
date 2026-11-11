<?php

namespace Tests\Feature;

use App\Actions\Issues\DeleteIssue;
use App\Actions\Issues\ForceDeleteIssue;
use App\Actions\Issues\RestoreIssue;
use App\Enums\Permissions;
use App\Livewire\Issues\IssueCreate;
use App\Livewire\Issues\IssueEdit;
use App\Livewire\Issues\IssuesTable;
use App\Livewire\Modals\Deletion;
use App\Models\Issue;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Throwable;

class IssuesCrudTest extends FeatureTestCase
{
    public function test_customer_options_show_names_and_save_the_selected_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Selected Customer']);
        $otherUser = User::factory()->create(['name' => 'Noncustomer User']);

        Livewire::actingAs($admin)->test(IssueCreate::class)
            ->assertSet('form.userId', null)
            ->assertSee($customer->name)
            ->assertDontSee('(' . $customer->id . ') ' . $customer->name)
            ->assertDontSee($otherUser->name)
            ->set('form.userId', $customer->id)
            ->call('submit')->assertHasNoErrors();

        $this->assertDatabaseHas('issues', ['user_id' => $customer->id]);
    }

    public function test_customer_is_preselected_only_when_explicitly_passed(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        Livewire::actingAs($admin)->test(IssueCreate::class, ['customerId' => $customer->id])
            ->assertSet('form.userId', $customer->id);
        Livewire::test(IssueCreate::class)->call('submit')->assertHasErrors('form.userId');

        $this->get(route('issues.create', ['customer_id' => $customer->id]))
            ->assertSeeLivewire(IssueCreate::class);
    }

    public function test_creates_and_edits_an_issue_with_optional_relationships(): void
    {
        $user = $this->owner();
        Livewire::actingAs($user)->test(IssueCreate::class)
            ->call('submit')->assertHasNoErrors();
        $issue = Issue::query()->sole();
        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'user_id' => $user->id, 'started_at' => null, 'finished_at' => null]);
        $quote = Quote::factory()->for($issue)->create();
        $work = Work::factory()->forCustomer($user)->for($issue)->create();

        Livewire::actingAs($this->editor())->test(IssueEdit::class, ['issue' => $issue])
            ->call('submit')->assertHasNoErrors();

        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'issue_id' => $issue->id]);
        $this->assertDatabaseHas('works', ['id' => $work->id, 'issue_id' => $issue->id]);
        $this->assertTrue($issue->fresh()->user->is($user));
        $this->assertTrue($quote->fresh()->issue->is($issue));
        $this->assertTrue($work->fresh()->issue->is($issue));
        $this->assertTrue($user->issues()->sole()->is($issue));
        $this->assertTrue($issue->quotes()->sole()->is($quote));
        $this->assertTrue($issue->works()->sole()->is($work));

        $this->assertSame($issue->id, $quote->fresh()->issue_id);
        $this->assertSame($issue->id, $work->fresh()->issue_id);
    }

    public function test_invalid_input_does_not_create_an_issue(): void
    {
        Livewire::actingAs($this->owner())->test(IssueCreate::class)
            ->set('form.userId', 999999)
            ->call('submit')->assertHasErrors('form.userId');

        $this->assertDatabaseCount('issues', 0);
    }

    public function test_own_creator_cannot_assign_other_owners(): void
    {
        $user = $this->owner();
        $other = User::factory()->create();

        Livewire::actingAs($user)->test(IssueCreate::class)
            ->set('form.userId', $other->id)->call('submit')->assertForbidden();

        $this->assertDatabaseCount('issues', 0);
    }

    public function test_own_edit_permission_does_not_allow_editing_an_issue(): void
    {
        $user = $this->owner();
        $issue = Issue::factory()->for($user)->create();

        Livewire::actingAs($user)->test(IssueEdit::class, ['issue' => $issue])->assertForbidden();

        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'user_id' => $user->id]);
    }

    public function test_edit_rechecks_permissions_after_mount(): void
    {
        $user = $this->editor();
        $issue = Issue::factory()->create();
        $originalOwnerId = $issue->user_id;
        $component = Livewire::actingAs($user)->test(IssueEdit::class, ['issue' => $issue])
            ->set('form.userId', User::factory()->create()->id);
        $user->revokePermissionTo(Permissions::ISSUE_EDIT_ALL->label());

        $component->call('submit')->assertForbidden();

        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'user_id' => $originalOwnerId]);
    }

    public function test_customer_search_scopes_issues_and_resets_pagination_and_sort_is_allowlisted(): void
    {
        $user = $this->owner();
        $user->update(['name' => 'Matching owner']);
        $own = Issue::factory()->for($user)->create();
        $other = Issue::factory()->create();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(IssuesTable::class, ['customer' => $user])
            ->call('setPage', 2)->set('search', 'Matching')
            ->assertSee('Případ #' . $own->id)->assertDontSee('Případ #' . $other->id)
            ->call('sort', 'created_at')->assertSet('sortBy', 'created_at');
        Livewire::test(IssuesTable::class, ['customer' => $user])->call('sort', 'invalid')->assertStatus(422);
    }

    public function test_index_table_renders_flux_pagination_and_moves_between_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $issues = Issue::factory()->count(16)->create();

        Livewire::actingAs($admin)->test(IssuesTable::class)
            ->assertSee('data-flux-pagination', false)
            ->assertSee("nextPage('page')", false)
            ->assertSee('href="' . route('issues.show', $issues->first()) . '"', false)
            ->assertDontSee('href="' . route('issues.show', $issues->last()) . '"', false)
            ->call('nextPage')
            ->assertSet('paginators.page', 2)
            ->assertSee('href="' . route('issues.show', $issues->last()) . '"', false)
            ->assertDontSee('href="' . route('issues.show', $issues->first()) . '"', false)
            ->call('previousPage')
            ->assertSet('paginators.page', 1)
            ->assertSee('href="' . route('issues.show', $issues->first()) . '"', false)
            ->assertDontSee('href="' . route('issues.show', $issues->last()) . '"', false);
    }

    /** @throws Throwable */
    public function test_confirmed_deletion_restore_and_force_delete_follow_permissions(): void
    {
        $user = User::factory()->admin()->create();
        $issue = Issue::factory()->create();
        $modal = Livewire::actingAs($user)->test(Deletion::class, ['model' => $issue, 'heading' => 'Smazat?', 'text' => 'Potvrďte.']);
        $modal->call('delete');
        $this->assertNotSoftDeleted($issue);
        $modal->call('confirmDelete')->call('delete');
        $this->assertSoftDeleted($issue);

        app(RestoreIssue::class)->handle($issue);
        $this->assertNotSoftDeleted($issue);
        $issue->delete();
        app(ForceDeleteIssue::class)->handle($issue);
        $this->assertModelMissing($issue);
    }

    public function test_unauthorized_delete_does_not_remove_the_issue(): void
    {
        $user = $this->owner();
        $issue = Issue::factory()->for($user)->create();
        Livewire::actingAs($user)->test(Deletion::class, ['model' => $issue, 'heading' => 'Smazat?', 'text' => 'Potvrďte.'])
            ->call('confirmDelete')->assertForbidden();
        $this->assertNotSoftDeleted($issue);
    }

    public function test_one_issue_can_have_multiple_quotes_and_works_and_they_cannot_be_shared(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $issue = Issue::factory()->for($owner)->create();
        $quotes = Quote::factory()->count(2)->for($issue)->create();
        $works = Work::factory()->count(2)->forCustomer($owner)->for($issue)->create();
        $this->actingAs($admin);
        $this->assertSame(2, $issue->quotes()->count());
        $this->assertSame(2, $issue->works()->count());
        foreach ($quotes->concat($works) as $record) {
            $this->assertTrue($record->fresh()->issue->is($issue));
        }
        $other = Issue::factory()->create();
        Livewire::test(IssueEdit::class, ['issue' => $other])
            ->call('submit')->assertHasNoErrors();
        $this->assertSame(2, $issue->quotes()->count());
        $this->assertSame(2, $issue->works()->count());
        $this->assertSame(0, $other->quotes()->count());
        $this->assertSame(0, $other->works()->count());
    }

    public function test_edit_preserves_archived_related_records(): void
    {
        $user = $this->owner();
        $issue = Issue::factory()->for($user)->create();
        $quote = Quote::factory()->for($issue)->trashed()->create();
        $work = Work::factory()->forCustomer($user)->for($issue)->trashed()->create();

        Livewire::actingAs($this->editor())->test(IssueEdit::class, ['issue' => $issue])
            ->call('submit')->assertHasNoErrors();

        $this->assertSame($issue->id, $quote->fresh()->issue_id);
        $this->assertSame($issue->id, $work->fresh()->issue_id);
        $this->assertSoftDeleted($quote);
        $this->assertSoftDeleted($work);
    }

    public function test_delete_rechecks_permission_after_confirmation(): void
    {
        $user = User::factory()->admin()->create();
        $issue = Issue::factory()->create();
        $modal = Livewire::actingAs($user)->test(Deletion::class, ['model' => $issue, 'heading' => 'Smazat?', 'text' => 'Potvrďte.'])
            ->call('confirmDelete');
        $user->syncRoles([]);
        $modal->call('delete')->assertForbidden();
        $this->assertNotSoftDeleted($issue);
    }

    /** @throws Throwable */
    public function test_restore_and_force_delete_deny_users_without_lifecycle_permissions(): void
    {
        $this->actingAs($user = $this->owner());
        $issue = Issue::factory()->for($user)->trashed()->create();
        $this->assertFalse($user->can('restore', $issue));
        $this->assertFalse($user->can('forceDelete', $issue));
        foreach ([RestoreIssue::class, ForceDeleteIssue::class] as $action) {
            try {
                app($action)->handle($issue);
                $this->fail('Unauthorized lifecycle action succeeded.');
            } catch (AuthorizationException) {
                $this->assertSoftDeleted($issue);
            }
        }
    }

    public function test_datetimes_are_created_loaded_updated_and_cleared(): void
    {
        $user = $this->owner();
        Livewire::actingAs($user)->test(IssueCreate::class)
            ->set('form.startedAt', '2026-11-09T10:30')
            ->set('form.finishedAt', '2026-11-09T12:45')
            ->call('submit')->assertHasNoErrors();
        $issue = Issue::query()->sole();
        $this->assertSame('2026-11-09 10:30:00', $issue->started_at->toDateTimeString());
        $this->assertSame('2026-11-09 12:45:00', $issue->finished_at->toDateTimeString());

        Livewire::actingAs($this->editor())->test(IssueEdit::class, ['issue' => $issue])
            ->assertSet('form.startedAt', '2026-11-09T10:30')
            ->assertSet('form.finishedAt', '2026-11-09T12:45')
            ->set('form.finishedAt', '2026-11-09T13:00')->call('submit')->assertHasNoErrors();
        $this->assertSame('2026-11-09 13:00:00', $issue->fresh()->finished_at->toDateTimeString());

        Livewire::actingAs($this->editor())->test(IssueEdit::class, ['issue' => $issue])
            ->set('form.startedAt', '')->set('form.finishedAt', '')
            ->call('submit')->assertHasNoErrors();
        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'started_at' => null, 'finished_at' => null]);
    }

    public function test_invalid_datetimes_and_finish_before_start_are_rejected(): void
    {
        Livewire::actingAs($this->owner())->test(IssueCreate::class)
            ->set('form.startedAt', 'invalid')->set('form.finishedAt', 'invalid')
            ->call('submit')->assertHasErrors(['form.startedAt', 'form.finishedAt'])
            ->set('form.startedAt', '2026-11-09T12:00')->set('form.finishedAt', '2026-11-09T11:00')
            ->call('submit')->assertHasErrors(['form.finishedAt' => 'after_or_equal']);
        $this->assertDatabaseCount('issues', 0);

        $issue = Issue::factory()->for(auth()->user())->create([
            'started_at' => '2026-11-09 10:00:00', 'finished_at' => '2026-11-09 12:00:00',
        ]);
        Livewire::actingAs($this->editor())->test(IssueEdit::class, ['issue' => $issue])
            ->set('form.startedAt', '2026-11-09T13:00')->call('submit')
            ->assertHasErrors(['form.finishedAt' => 'after_or_equal']);
        $this->assertSame('2026-11-09 10:00:00', $issue->fresh()->started_at->toDateTimeString());
    }

    public function test_editing_issue_does_not_require_permission_to_update_related_records(): void
    {
        $user = $this->owner();
        $issue = Issue::factory()->for($user)->create();
        $quote = Quote::factory()->for($issue)->create();
        $work = Work::factory()->forCustomer($user)->for($issue)->create();

        Livewire::actingAs($this->editor())->test(IssueEdit::class, ['issue' => $issue])
            ->call('submit')->assertHasNoErrors();

        $this->assertSame($issue->id, $quote->fresh()->issue_id);
        $this->assertSame($issue->id, $work->fresh()->issue_id);
    }

    /** @throws Throwable */
    public function test_deleting_an_issue_preserves_related_records_and_works_prevent_force_deletion(): void
    {
        $user = User::factory()->admin()->create();
        $issue = Issue::factory()->create();
        $quote = Quote::factory()->for($issue)->create();
        $work = Work::factory()->for($issue)->create();
        $this->actingAs($user);
        app(DeleteIssue::class)->handle($issue);
        $this->assertSame($issue->id, $quote->fresh()->issue_id);
        $this->assertSame($issue->id, $work->fresh()->issue_id);
        $this->assertFalse($user->can('forceDelete', $issue));
        $work->forceDelete();
        $this->assertFalse($user->can('forceDelete', $issue));
        $quote->forceDelete();
        app(ForceDeleteIssue::class)->handle($issue);
        $this->assertModelMissing($issue);
    }

    private function editor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::ISSUE_EDIT_ALL->label());

        return $user;
    }

    private function owner(): User
    {
        $role = Role::create(['name' => 'Issue owner', 'guard_name' => 'web']);
        $role->givePermissionTo([
            Permissions::ISSUE_VIEW_OWN->label(), Permissions::ISSUE_CREATE_OWN->label(),
            Permissions::ISSUE_EDIT_OWN->label(), Permissions::QUOTE_VIEW_OWN->label(), Permissions::WORK_VIEW_OWN->label(),
            Permissions::QUOTE_EDIT_OWN->label(), Permissions::WORK_EDIT_OWN->label(),
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
