<?php

namespace Tests\Feature\ActivityLogs;

use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\ActivityLogs\ActivityLogsTable;
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

    private function viewer(): User
    {
        return User::factory()->create()->assignRole(Roles::ADMIN->value);
    }
}
