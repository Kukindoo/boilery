<?php

namespace Tests\Feature\ActivityLogs;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\ActivityLogs\ActivityLogsTable;
use App\Models\File;
use App\Models\Quote;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

class ActivityLogsTableTest extends FeatureTestCase
{
    public function test_activity_page_requires_authentication_and_permission(): void
    {
        $this->get(route('activity-logs.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('activity-logs.index'))->assertForbidden();
        $this->actingAs($this->viewer())->get(route('activity-logs.index'))
            ->assertOk()->assertSeeLivewire(ActivityLogsTable::class);
    }

    public function test_displays_all_logs_newest_first_with_causer_and_changes(): void
    {
        $user = $this->viewer();
        Activity::query()->create(['description' => 'Older activity', 'created_at' => '2026-01-01']);
        Activity::query()->create([
            'description' => 'Newer activity', 'created_at' => '2026-01-02',
            'subject_type' => Work::class, 'subject_id' => 99,
            'causer_type' => User::class, 'causer_id' => $user->id,
            'event' => 'updated', 'attribute_changes' => ['attributes' => ['status' => 'finished']],
        ]);

        Livewire::actingAs($user)->test(ActivityLogsTable::class)
            ->assertSeeInOrder(['Newer activity', 'Older activity'])
            ->assertSee($user->name)->assertSee('Work')->assertSee('99')
            ->assertSee('updated')->assertSee('finished')->assertSee('Systém / neznámý');
    }

    public function test_filters_by_model_type_and_by_subject_id_without_crossing_model_types(): void
    {
        Activity::query()->create(['description' => 'First work', 'subject_type' => Work::class, 'subject_id' => 1]);
        Activity::query()->create(['description' => 'Second work', 'subject_type' => Work::class, 'subject_id' => 2]);
        Activity::query()->create(['description' => 'Same ID quote', 'subject_type' => Quote::class, 'subject_id' => 1]);
        $user = $this->viewer();

        Livewire::actingAs($user)->test(ActivityLogsTable::class, ['subjectType' => Work::class])
            ->assertSee('First work')->assertSee('Second work')->assertDontSee('Same ID quote');
        Livewire::actingAs($user)->test(ActivityLogsTable::class, ['subjectType' => Work::class, 'subjectId' => 1])
            ->assertSee('First work')->assertDontSee('Second work')->assertDontSee('Same ID quote');
    }

    public function test_resolves_morph_aliases_for_model_filters(): void
    {
        $previousMap = Relation::morphMap();
        Relation::morphMap(['work' => Work::class]);

        try {
            Activity::query()->create(['description' => 'Aliased work', 'subject_type' => 'work', 'subject_id' => 1]);
            $user = $this->viewer();
            foreach ([Work::class, 'work'] as $type) {
                Livewire::actingAs($user)->test(ActivityLogsTable::class, ['subjectType' => $type, 'subjectId' => 1])
                    ->assertSee('Aliased work');
            }
        } finally {
            Relation::morphMap($previousMap, false);
        }
    }

    public function test_rejects_ambiguous_or_invalid_subject_filters(): void
    {
        $user = $this->viewer();
        Livewire::actingAs($user)->test(ActivityLogsTable::class, ['subjectId' => 1])->assertStatus(422);
        Livewire::actingAs($user)->test(ActivityLogsTable::class, ['subjectType' => 'invalid'])->assertStatus(422);
    }

    public function test_paginates_logs_with_stable_order_for_equal_timestamps(): void
    {
        for ($index = 0; $index < 16; $index++) {
            Activity::query()->create(['description' => sprintf('Activity-%02d', $index), 'created_at' => '2026-01-01']);
        }

        Livewire::actingAs($this->viewer())->test(ActivityLogsTable::class)
            ->assertSee('Activity-15')->assertDontSee('Activity-00')
            ->call('setPage', 2, 'activityPage')
            ->assertSee('Activity-00')->assertDontSee('Activity-15');
    }

    public function test_rechecks_permission_on_subsequent_livewire_requests(): void
    {
        Livewire::actingAs(User::factory()->create())->test(ActivityLogsTable::class)->assertForbidden();
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::ACTIVITY_LOG_VIEW_ALL->label());
        $component = Livewire::actingAs($user)->test(ActivityLogsTable::class);
        $user->revokePermissionTo(Permissions::ACTIVITY_LOG_VIEW_ALL->label());
        $component->call('setPage', 2, 'activityPage')->assertForbidden();
    }

    public function test_scoped_empty_state_and_escaped_log_content(): void
    {
        $user = $this->viewer();
        Activity::query()->create(['description' => '<script>alert(1)</script>']);
        Livewire::actingAs($user)->test(ActivityLogsTable::class)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        Livewire::actingAs($user)->test(ActivityLogsTable::class, ['subjectType' => Work::class, 'subjectId' => 999])
            ->assertSee('Žádné aktivity.');
    }

    public function test_subject_type_cannot_be_changed_by_the_client(): void
    {
        $component = Livewire::actingAs($this->viewer())->test(ActivityLogsTable::class, ['subjectType' => Work::class, 'subjectId' => 1]);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('subjectType', Quote::class);
    }

    public function test_subject_id_cannot_be_changed_by_the_client(): void
    {
        $component = Livewire::actingAs($this->viewer())->test(ActivityLogsTable::class, ['subjectType' => Work::class, 'subjectId' => 1]);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('subjectId', 2);
    }

    public function test_filters_by_causer_and_excludes_logs_where_user_is_only_subject(): void
    {
        $causer = User::factory()->create();
        $other = User::factory()->create();
        Activity::query()->create(['description' => 'Performed by selected user', 'causer_type' => $causer->getMorphClass(), 'causer_id' => $causer->id]);
        Activity::query()->create(['description' => 'About selected user', 'subject_type' => $causer->getMorphClass(), 'subject_id' => $causer->id, 'causer_type' => $other->getMorphClass(), 'causer_id' => $other->id]);
        Activity::query()->create(['description' => 'Different causer type', 'causer_type' => Work::class, 'causer_id' => $causer->id]);
        Activity::query()->create(['description' => 'System activity']);

        Livewire::actingAs($this->viewer())->test(ActivityLogsTable::class, ['causer' => $causer])
            ->assertSee('Performed by selected user')->assertDontSee('About selected user')
            ->assertDontSee('Different causer type')->assertDontSee('System activity');
    }

    public function test_links_users_only_when_viewer_can_view_them(): void
    {
        $causer = User::factory()->create();
        Activity::query()->create(['description' => 'User action', 'causer_type' => $causer->getMorphClass(), 'causer_id' => $causer->id]);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permissions::ACTIVITY_LOG_VIEW_ALL->label());

        Livewire::actingAs($viewer)->test(ActivityLogsTable::class)
            ->assertSee($causer->name)->assertDontSee('href="' . route('customers.show', $causer) . '"', false);
        $viewer->givePermissionTo(Permissions::USER_VIEW_ALL->label());
        Livewire::actingAs($viewer)->test(ActivityLogsTable::class)
            ->assertSee('href="' . route('customers.show', $causer) . '"', false);
    }

    public function test_customer_page_displays_only_activities_caused_by_that_customer(): void
    {
        $viewer = $this->viewer();
        $viewer->givePermissionTo([Permissions::USER_VIEW_ALL->label(), Permissions::ISSUE_VIEW_ALL->label()]);
        $customer = User::factory()->create();
        Activity::query()->create(['description' => 'Customer performed action', 'causer_type' => $customer->getMorphClass(), 'causer_id' => $customer->id]);
        Activity::query()->create(['description' => 'Unrelated activity']);

        $this->actingAs($viewer)->get(route('customers.show', $customer))
            ->assertOk()->assertSee('Customer performed action')->assertDontSee('Unrelated activity');
    }

    public function test_causer_cannot_be_changed_by_the_client(): void
    {
        $causer = User::factory()->create();
        $component = Livewire::actingAs($this->viewer())->test(ActivityLogsTable::class, ['causer' => $causer]);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('causer', null);
    }

    public function test_links_subject_and_causer_ids_only_with_view_permission(): void
    {
        $causer = User::factory()->create();
        $work = Work::factory()->forCustomer($causer)->create();
        Activity::query()->create([
            'description' => 'Linked record',
            'subject_type' => $work->getMorphClass(), 'subject_id' => $work->id,
            'causer_type' => $causer->getMorphClass(), 'causer_id' => $causer->id,
        ]);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(Permissions::ACTIVITY_LOG_VIEW_ALL->label());

        Livewire::actingAs($viewer)->test(ActivityLogsTable::class)
            ->assertDontSee('href="' . route('works.show', $work) . '"', false)
            ->assertDontSee('href="' . route('customers.show', $causer) . '"', false);

        $viewer->givePermissionTo([Permissions::WORK_VIEW_ALL->label(), Permissions::USER_VIEW_ALL->label()]);
        Livewire::actingAs($viewer)->test(ActivityLogsTable::class)
            ->assertSee('href="' . route('works.show', $work) . '"', false)
            ->assertSee('>' . $work->id . '</a>', false)
            ->assertSee('>' . $causer->id . '</a>', false);

        $work->delete();
        Livewire::actingAs($viewer)->test(ActivityLogsTable::class)
            ->assertSee('Linked record')
            ->assertDontSee('href="' . route('works.show', $work) . '"', false);
    }

    public function test_non_admin_can_access_activity_with_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::ACTIVITY_LOG_VIEW_ALL->label());

        Livewire::actingAs($user)->test(ActivityLogsTable::class)->assertOk();
        $this->actingAs($user)->get(route('activity-logs.index'))
            ->assertOk()->assertSeeLivewire(ActivityLogsTable::class);
    }

    public function test_searches_activity_text_metadata_ids_and_causer(): void
    {
        $causer = User::factory()->create(['name' => 'Searchable actor', 'email' => 'searchable@example.test']);
        $entry = Activity::query()->create([
            'id' => 87654321, 'description' => 'Searchable upload', 'log_name' => 'files', 'event' => 'action_completed',
            'subject_type' => Work::class, 'subject_id' => 987654,
            'causer_type' => $causer->getMorphClass(), 'causer_id' => $causer->id,
            'properties' => ['filename' => 'receipt-special.pdf'],
            'attribute_changes' => ['attributes' => ['status' => 'special-status']],
        ]);
        Activity::query()->create(['description' => 'Unrelated entry']);
        $component = Livewire::actingAs($this->viewer())->test(ActivityLogsTable::class)
            ->set('selectedModel', Work::class);

        foreach (['Searchable upload', 'Searchable actor', 'searchable@example.test', '987654', 'receipt-special.pdf', 'special-status', 'action_completed'] as $search) {
            $component->set('search', $search);
            $this->assertSame([$entry->id], $component->get('activities')->modelKeys());
        }
        $component->set('search', (string) $entry->id);
        $this->assertCount(0, $component->get('activities'));
        $component->set('search', 'no matching activity');
        $this->assertCount(0, $component->get('activities'));
    }

    public function test_combines_select_filters_and_resets_pagination(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();
        foreach ([[$actor, Work::class, 'updated'], [$other, Work::class, 'updated'], [$actor, Quote::class, 'updated'], [$actor, Work::class, 'created']] as [$user, $model, $event]) {
            $entry = Activity::query()->create(['description' => 'Filtered activity', 'causer_type' => $user->getMorphClass(), 'causer_id' => $user->id, 'subject_type' => $model, 'subject_id' => 99999, 'event' => $event]);
            $expectedId ??= $entry->id;
        }
        $component = Livewire::actingAs($this->viewer())->test(ActivityLogsTable::class);
        foreach (['selectedUser' => (string) $actor->id, 'selectedModel' => Work::class, 'selectedEvent' => 'updated'] as $filter => $value) {
            $component->call('setPage', 2, 'activityPage')->set($filter, $value)->assertSet('paginators.activityPage', 1);
        }
        $this->assertSame([$expectedId], $component->get('activities')->modelKeys());
    }

    public function test_clearing_filters_preserves_subject_and_causer_scope(): void
    {
        $actor = User::factory()->create();
        $entry = Activity::query()->create(['description' => 'In scope', 'subject_type' => Work::class, 'subject_id' => 99999, 'causer_type' => $actor->getMorphClass(), 'causer_id' => $actor->id, 'event' => 'updated']);
        Activity::query()->create(['description' => 'Out of scope', 'subject_type' => Quote::class, 'subject_id' => 99999, 'event' => 'created']);
        $component = Livewire::actingAs($this->viewer())->test(ActivityLogsTable::class, ['subjectType' => Work::class, 'subjectId' => 99999, 'causer' => $actor]);
        $this->assertSame([Work::class], $component->get('filterModels')->all());
        $this->assertSame(['updated'], $component->get('filterEvents')->all());
        $this->assertSame([$actor->id], $component->get('filterUsers')->modelKeys());
        $component->set('search', 'missing')->set('selectedUser', '999999')->set('selectedModel', Quote::class)->set('selectedEvent', 'created')
            ->call('setPage', 2, 'activityPage')->call('clearFilters')
            ->assertSet('search', '')->assertSet('selectedUser', null)->assertSet('selectedModel', null)->assertSet('selectedEvent', null)
            ->assertSet('paginators.activityPage', 1);
        $this->assertSame([$entry->id], $component->get('activities')->modelKeys());
    }

    public function test_numeric_search_matches_only_exact_subject_id_with_selected_model(): void
    {
        $entry = Activity::query()->create(['description' => 'Exact file', 'subject_type' => File::class, 'subject_id' => 100]);
        Activity::query()->create(['description' => 'Partial ID', 'subject_type' => File::class, 'subject_id' => 1000]);
        Activity::query()->create(['description' => 'Contains 100', 'subject_type' => File::class, 'subject_id' => 200, 'properties' => ['file_id' => 100], 'causer_type' => User::class, 'causer_id' => 100]);
        Activity::query()->create(['description' => 'Other model', 'subject_type' => Work::class, 'subject_id' => 100]);

        $component = Livewire::actingAs($this->viewer())->test(ActivityLogsTable::class)
            ->set('selectedModel', File::class)->set('search', ' 100 ');

        $this->assertSame([$entry->id], $component->get('activities')->modelKeys());
    }

    private function viewer(): User
    {
        return User::factory()->create()->assignRole(Roles::ADMIN->value);
    }
}
