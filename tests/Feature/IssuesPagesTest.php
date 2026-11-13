<?php

namespace Tests\Feature;

use App\Enums\Permissions;
use App\Livewire\ActivityLogs\ActivityLogsTable;
use App\Livewire\Issues\IssueCreate;
use App\Livewire\Issues\IssueEdit;
use App\Models\Issue;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class IssuesPagesTest extends FeatureTestCase
{
    public function test_pages_require_authentication_and_permissions(): void
    {
        $issue = Issue::factory()->create();
        foreach (['issues.index', 'issues.create', 'issues.show', 'issues.edit'] as $route) {
            $this->get(route($route, $issue))->assertRedirect(route('login'));
        }
        $this->actingAs(User::factory()->create());
        foreach (['issues.index', 'issues.create', 'issues.show', 'issues.edit'] as $route) {
            $this->get(route($route, $issue))->assertForbidden();
        }
    }

    public function test_admin_can_access_pages_and_missing_records_return_404(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $issue = Issue::factory()->create();

        $this->get(route('issues.index'))->assertSee('Případ #' . $issue->id);
        $this->get(route('issues.create'))->assertSeeLivewire(IssueCreate::class);
        $this->get(route('issues.edit', $issue))->assertSeeLivewire(IssueEdit::class);
        $this->get(route('issues.show', $issue))->assertSee('Případ #' . $issue->id);
        $this->get(route('issues.show', 999999))->assertNotFound();
        $this->get(route('issues.edit', 999999))->assertNotFound();
    }

    public function test_issue_activity_is_scoped_and_requires_activity_permission(): void
    {
        $issue = Issue::factory()->create();
        $other = Issue::factory()->create();
        Activity::query()->create(['description' => 'This issue activity', 'subject_type' => $issue->getMorphClass(), 'subject_id' => $issue->id]);
        Activity::query()->create(['description' => 'Other issue activity', 'subject_type' => $other->getMorphClass(), 'subject_id' => $other->id]);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permissions::ISSUE_VIEW_ALL->label());
        $this->actingAs($viewer)->get(route('issues.show', $issue))->assertOk()
            ->assertDontSeeLivewire(ActivityLogsTable::class)->assertDontSee('This issue activity');

        $viewer->givePermissionTo(Permissions::ACTIVITY_LOG_VIEW_ALL->label());
        $this->get(route('issues.show', $issue))->assertOk()
            ->assertSeeLivewire(ActivityLogsTable::class)
            ->assertSee('This issue activity')->assertDontSee('Other issue activity');
    }

    public function test_own_permissions_do_not_expose_other_issues(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Issue owner', 'guard_name' => 'web']);
        $role->givePermissionTo([Permissions::ISSUE_VIEW_OWN->label(), Permissions::ISSUE_EDIT_OWN->label()]);
        $user->assignRole($role);
        $own = Issue::factory()->for($user)->create();
        $other = Issue::factory()->create();
        $this->actingAs($user);

        $this->get(route('issues.index'))->assertForbidden();
        $this->get(route('issues.show', $own))->assertSee('Případ #' . $own->id);
        $this->get(route('issues.edit', $own))->assertForbidden();
        $this->get(route('issues.show', $other))->assertForbidden();
        $this->get(route('issues.edit', $other))->assertForbidden();
    }
}
