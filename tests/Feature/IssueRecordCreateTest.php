<?php

namespace Tests\Feature;

use App\Enums\Permissions;
use App\Livewire\Issues\IssueRecordCreate;
use App\Models\Issue;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

class IssueRecordCreateTest extends FeatureTestCase
{
    public function test_creates_empty_quote_for_existing_issue_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->givePermissionTo(Permissions::QUOTE_CREATE_ALL->label());
        $customer = User::factory()->customer()->create(['name' => 'Jana Novakova']);
        $issue = Issue::factory()->for($customer)->create();

        $component = Livewire::actingAs($admin)->test(IssueRecordCreate::class, ['issue' => $issue, 'type' => 'quote'])
            ->call('create')->assertStatus(200)->assertHasNoErrors();

        $quote = $issue->quotes()->sole();
        $component->assertRedirect(route('quotes.show', $quote));
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id, 'issue_id' => $issue->id, 'first_name' => 'Jana',
            'last_name' => 'Novakova', 'email' => $customer->email,
            'phone' => $customer->phone ?? '', 'message' => '',
        ]);
        $this->assertDatabaseCount('issues', 1);
    }

    public function test_creates_empty_work_for_existing_issue_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->givePermissionTo(Permissions::QUOTE_CREATE_ALL->label());
        $issue = Issue::factory()->create();

        $component = Livewire::actingAs($admin)->test(IssueRecordCreate::class, ['issue' => $issue, 'type' => 'work'])
            ->call('create')->assertStatus(200)->assertHasNoErrors();

        $work = $issue->works()->sole();
        $component->assertRedirect(route('works.show', $work));
        $this->assertDatabaseHas('works', [
            'id' => $work->id, 'issue_id' => $issue->id,
            'quote_id' => null, 'reported_problem' => null,
        ]);
        $this->assertNotEmpty($work->order_id);
        $this->assertDatabaseCount('issues', 1);
    }

    public function test_creation_rechecks_permissions_after_page_load(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->givePermissionTo(Permissions::QUOTE_CREATE_ALL->label());
        $issue = Issue::factory()->create();
        foreach (['quote', 'work'] as $type) {
            $admin->assignRole(\App\Enums\Roles::ADMIN->value);
            $component = Livewire::actingAs($admin)->test(IssueRecordCreate::class, ['issue' => $issue, 'type' => $type]);
            $admin->syncRoles([]);
            $admin->syncPermissions([]);
            $component->call('create')->assertForbidden();
        }

        $this->assertDatabaseCount('quotes', 0);
        $this->assertDatabaseCount('works', 0);
    }

    public function test_issue_edit_permission_does_not_grant_record_creation(): void
    {
        $role = Role::create(['name' => 'Issue editor', 'guard_name' => 'web']);
        $role->givePermissionTo([Permissions::ISSUE_VIEW_ALL->label(), Permissions::ISSUE_EDIT_ALL->label()]);
        $user = User::factory()->create();
        $user->assignRole($role);
        $issue = Issue::factory()->create();

        foreach (['quote', 'work'] as $type) {
            Livewire::actingAs($user)->test(IssueRecordCreate::class, ['issue' => $issue, 'type' => $type])
                ->call('create')->assertForbidden();
        }

        $this->assertDatabaseCount('quotes', 0);
        $this->assertDatabaseCount('works', 0);
    }

    public function test_issue_page_shows_buttons_in_both_tables(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->givePermissionTo(Permissions::QUOTE_CREATE_ALL->label());
        $this->actingAs($admin);
        $issue = Issue::factory()->create();

        $this->get(route('issues.show', $issue))
            ->assertSee('Vytvořit poptávku')->assertSee('Vytvořit zakázku');
    }
}
