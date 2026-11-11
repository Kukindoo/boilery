<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Enums\Permissions;
use App\Livewire\Issues\IssueCreate;
use App\Livewire\Issues\IssueEdit;
use App\Livewire\Issues\IssuesTable;
use App\Models\Issue;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

class IssueStatusTest extends FeatureTestCase
{
    public function test_new_cases_default_to_open_and_can_be_closed_and_reopened(): void
    {
        $user = User::factory()->admin()->create();
        Livewire::actingAs($user)->test(IssueCreate::class)->set('form.userId', User::factory()->customer()->create()->id)
            ->assertSet('form.status', 'open')->call('submit')->assertHasNoErrors();
        $issue = Issue::query()->sole();
        $this->assertSame(IssueStatus::OPEN, $issue->status);

        Livewire::test(IssueEdit::class, ['issue' => $issue])
            ->set('form.status', 'closed')->call('submit')->assertHasNoErrors();
        $this->assertSame(IssueStatus::CLOSED, $issue->fresh()->status);
        $this->actingAs($user)->get(route('issues.show', $issue))->assertSee('Uzavřený');

        Livewire::test(IssueEdit::class, ['issue' => $issue->fresh()])
            ->assertSet('form.status', 'closed')->set('form.status', 'open')
            ->call('submit')->assertHasNoErrors();
        $this->assertSame(IssueStatus::OPEN, $issue->fresh()->status);
    }

    public function test_invalid_statuses_are_not_saved(): void
    {
        $user = User::factory()->admin()->create();
        Livewire::actingAs($user)->test(IssueCreate::class)->set('form.userId', User::factory()->customer()->create()->id)
            ->set('form.status', 'invalid')->call('submit')->assertHasErrors('form.status');
        $this->assertDatabaseCount('issues', 0);
        $issue = Issue::factory()->create();
        Livewire::test(IssueEdit::class, ['issue' => $issue])
            ->set('form.status', 'invalid')->call('submit')->assertHasErrors('form.status');
        $this->assertSame(IssueStatus::OPEN, $issue->fresh()->status);
    }

    public function test_status_filter_defaults_to_open_and_supports_closed_and_both(): void
    {
        $user = User::factory()->admin()->create();
        $open = Issue::factory()->create();
        $closed = Issue::factory()->closed()->create();
        $component = Livewire::actingAs($user)->test(IssuesTable::class)
            ->assertSet('selectedStatuses', ['open'])
            ->assertSee('Případ #' . $open->id)->assertDontSee('Případ #' . $closed->id);
        $component->call('setPage', 2)->set('selectedStatuses', ['closed'])
            ->assertSee('Případ #' . $closed->id)->assertDontSee('Případ #' . $open->id);
        $component->set('selectedStatuses', ['open', 'closed'])
            ->assertSee('Případ #' . $open->id)->assertSee('Případ #' . $closed->id);
        $component->set('selectedStatuses', [])->assertSee('Žádné případy.');
    }

    public function test_closed_filter_and_search_preserve_owner_scoping(): void
    {
        $role = Role::create(['name' => 'Case reader', 'guard_name' => 'web']);
        $role->givePermissionTo([Permissions::ISSUE_VIEW_OWN->label(), Permissions::USER_VIEW_OWN->label()]);
        $user = User::factory()->create(['name' => 'Matching owner']);
        $user->assignRole($role);
        $own = Issue::factory()->for($user)->closed()->create();
        $other = Issue::factory()->for(User::factory()->create(['name' => 'Matching stranger']))->closed()->create();
        Livewire::actingAs($user)->test(IssuesTable::class, ['customer' => $user])
            ->set('selectedStatuses', ['closed'])->set('search', 'Matching')
            ->assertSee('Případ #' . $own->id)->assertDontSee('Případ #' . $other->id);
    }
}
