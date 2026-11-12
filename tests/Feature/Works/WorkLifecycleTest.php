<?php

namespace Tests\Feature\Works;

use App\Enums\Permissions;
use App\Enums\WorkStatus;
use App\Livewire\Works\WorkEdit;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\FeatureTestCase;

class WorkLifecycleTest extends FeatureTestCase
{
    /** @return array<string, array{string, WorkStatus, WorkStatus, string, string}> */
    public static function transitions(): array
    {
        return [
            'start' => ['start', WorkStatus::NEW, WorkStatus::PROCESSING, 'work_started_at', 'Work started'],
            'restart finished' => ['start', WorkStatus::FINISHED, WorkStatus::PROCESSING, 'work_started_at', 'Work started'],
            'restart cancelled' => ['start', WorkStatus::CANCELLED, WorkStatus::PROCESSING, 'work_started_at', 'Work started'],
            'end' => ['end', WorkStatus::PROCESSING, WorkStatus::FINISHED, 'work_completed_at', 'Work finished'],
        ];
    }

    /** @return array<string, array{string, WorkStatus}> */
    public static function endpoints(): array
    {
        return [
            'start' => ['start', WorkStatus::NEW],
            'end' => ['end', WorkStatus::PROCESSING],
            'cancel' => ['cancel', WorkStatus::PROCESSING],
        ];
    }

    #[DataProvider('transitions')]
    public function test_transition_persists_status_date_and_activity_and_cannot_be_repeated(string $action, WorkStatus $initial, WorkStatus $expected, string $dateColumn, string $description): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(14, 30));
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_EDIT_ALL, Permissions::WORK_VIEW_ALL]);
        $work = Work::factory()->create([
            'status' => $initial,
            'work_accepted_at' => '2026-11-01',
            'work_started_at' => $initial === WorkStatus::NEW ? null : '2026-11-02',
            'work_completed_at' => in_array($initial, [WorkStatus::FINISHED, WorkStatus::CANCELLED], true) ? '2026-11-02' : null,
            'notes' => 'Keep these notes',
        ]);

        $this->actingAs($user)->post(route('works.' . $action, $work))
            ->assertRedirect(route('works.show', $work));

        $work->refresh();
        $this->assertSame($expected, $work->status);
        $this->assertSame('2026-11-03', $work->{$dateColumn}->toDateString());
        $this->assertSame('2026-11-01', $work->work_accepted_at->toDateString());
        $this->assertSame('Keep these notes', $work->notes);
        $this->assertSame($initial === WorkStatus::PROCESSING ? '2026-11-02' : '2026-11-03', $work->work_started_at->toDateString());
        $this->assertSame($action === 'end' ? '2026-11-03' : null, $work->work_completed_at?->toDateString());
        $activity = Activity::forSubject($work)->where('description', $description)->sole();
        $this->assertTrue($activity->causer->is($user));
        $attributes = $work->refresh()->getRawOriginal();
        $activityCount = Activity::forSubject($work)->count();
        $this->travel(1)->days();

        $this->post(route('works.' . $action, $work))->assertForbidden();

        $this->assertSame($attributes, $work->refresh()->getRawOriginal());
        $this->assertSame($activityCount + 1, Activity::forSubject($work)->count());
    }

    #[DataProvider('endpoints')]
    public function test_guests_and_users_without_permission_cannot_transition_work(string $action, WorkStatus $initial): void
    {
        $work = Work::factory()->create(['status' => $initial]);
        $attributes = $work->refresh()->getRawOriginal();
        $activityCount = Activity::forSubject($work)->count();

        $this->post(route('works.' . $action, $work))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post(route('works.' . $action, $work))->assertForbidden();

        $this->assertSame($attributes, $work->refresh()->getRawOriginal());
        $this->assertSame($activityCount + 1, Activity::forSubject($work)->count());
    }

    #[DataProvider('endpoints')]
    public function test_missing_and_deleted_works_cannot_be_transitioned(string $action, WorkStatus $initial): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permissions::WORK_EDIT_ALL);
        $work = Work::factory()->trashed()->create(['status' => $initial]);
        $activityCount = Activity::forSubject($work)->count();

        $this->actingAs($user)->post(route('works.' . $action, 999999))->assertNotFound();
        $this->post(route('works.' . $action, $work))->assertNotFound();

        $this->assertSame($initial, $work->refresh()->status);
        $this->assertSoftDeleted($work);
        $this->assertSame($activityCount, Activity::forSubject($work)->count());
    }

    public function test_restarting_finished_work_clears_completion_and_allows_editing_notes(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(14, 30));
        $user = User::factory()->create();
        $user->givePermissionTo([Permissions::WORK_EDIT_ALL, Permissions::WORK_VIEW_ALL]);
        $work = Work::factory()->create([
            'status' => WorkStatus::PROCESSING,
            'work_accepted_at' => '2026-11-01',
            'work_started_at' => '2026-11-02',
        ]);

        $this->actingAs($user)->post(route('works.end', $work))
            ->assertRedirect(route('works.show', $work));
        $this->assertSame('2026-11-03', $work->refresh()->work_completed_at->toDateString());
        $this->travel(8)->days();

        $this->post(route('works.start', $work))->assertRedirect(route('works.show', $work));

        $work->refresh();
        $this->assertSame(WorkStatus::PROCESSING, $work->status);
        $this->assertSame('2026-11-11', $work->work_started_at->toDateString());
        $this->assertNull($work->work_completed_at);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('form.notes', 'Updated after restart')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('works.show', $work));

        $this->assertSame('Updated after restart', $work->refresh()->notes);
        $this->assertSame(WorkStatus::PROCESSING, $work->status);
        $this->assertNull($work->work_completed_at);
    }

    public function test_show_exposes_only_the_available_transition_to_authorized_users(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permissions::WORK_VIEW_ALL,
            Permissions::WORK_EDIT_ALL,
            Permissions::ASSEMBLY_ACTION_VIEW_ALL,
        ]);
        $work = Work::factory()->create();

        $this->actingAs($user)->get(route('works.show', $work))->assertOk()
            ->assertSee('Zahájit zakázku')->assertDontSee('Restartovat zakázku')->assertDontSee('Dokončit zakázku')->assertDontSee('Zrušit zakázku');
        $this->post(route('works.start', $work));
        $this->get(route('works.show', $work))->assertOk()
            ->assertDontSee('Zahájit zakázku')->assertSee('Dokončit zakázku')->assertSee('Zrušit zakázku');
        $this->post(route('works.end', $work));
        $this->get(route('works.show', $work))->assertOk()
            ->assertSee('Restartovat zakázku')->assertDontSee('Zahájit zakázku')->assertDontSee('Dokončit zakázku')->assertDontSee('Zrušit zakázku');

        $this->post(route('works.start', $work))->assertRedirect(route('works.show', $work));
        $this->post(route('works.cancel', $work))->assertRedirect(route('works.show', $work));
        $this->get(route('works.show', $work))->assertOk()
            ->assertSee('Restartovat zakázku')->assertDontSee('Zahájit zakázku')->assertDontSee('Dokončit zakázku')->assertDontSee('Zrušit zakázku');

        $user->revokePermissionTo(Permissions::WORK_EDIT_ALL);
        $newWork = Work::factory()->create();
        $this->get(route('works.show', $newWork))->assertOk()
            ->assertDontSee('Zahájit zakázku')->assertDontSee('Dokončit zakázku')->assertDontSee('Zrušit zakázku');
    }
}
