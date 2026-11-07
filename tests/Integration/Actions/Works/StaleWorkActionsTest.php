<?php

namespace Tests\Integration\Actions\Works;

use App\Actions\Works\CancelWork;
use App\Actions\Works\DeleteWork;
use App\Actions\Works\EndWork;
use App\Actions\Works\ForceDeleteWork;
use App\Actions\Works\RestoreWork;
use App\Actions\Works\StartWork;
use App\Actions\Works\UpdateWork;
use App\Dtos\Works\WorkDto;
use App\Enums\Permissions;
use App\Enums\WorkStatus;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\Integration\IntegrationTestCase;

class StaleWorkActionsTest extends IntegrationTestCase
{
    /** @return array<string, array{class-string, WorkStatus, bool, array<string, mixed>}> */
    public static function invalidatedStates(): array
    {
        return [
            'already started' => [StartWork::class, WorkStatus::NEW, false, ['status' => WorkStatus::PROCESSING]],
            'cancelled before completion' => [EndWork::class, WorkStatus::PROCESSING, false, ['status' => WorkStatus::CANCELLED]],
            'finished before cancellation' => [CancelWork::class, WorkStatus::PROCESSING, false, ['status' => WorkStatus::FINISHED]],
            'already deleted' => [DeleteWork::class, WorkStatus::NEW, false, ['deleted_at' => '2026-11-06 12:00:00']],
            'already restored' => [RestoreWork::class, WorkStatus::NEW, true, ['deleted_at' => null]],
            'restored before permanent deletion' => [ForceDeleteWork::class, WorkStatus::NEW, true, ['deleted_at' => null]],
        ];
    }

    public static function ownershipActions(): array
    {
        return ['update' => [UpdateWork::class, false], 'restore' => [RestoreWork::class, true]];
    }

    public static function lifecycleActions(): array
    {
        return [
            'start' => [StartWork::class, WorkStatus::NEW, 'Work started'],
            'end' => [EndWork::class, WorkStatus::PROCESSING, 'Work finished'],
            'cancel' => [CancelWork::class, WorkStatus::PROCESSING, 'Work cancelled'],
        ];
    }

    #[DataProvider('invalidatedStates')]
    public function test_authorizes_current_state_and_preserves_changes_from_another_request(string $action, WorkStatus $initial, bool $trashed, array $changes): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $stale = Work::factory()->create(['status' => $initial, 'deleted_at' => $trashed ? now() : null]);
        Work::withTrashed()->whereKey($stale->id)->update($changes);
        $before = $stale->fresh()->getRawOriginal();
        $activityCount = Activity::forSubject($stale)->count();

        try {
            app($action)->handle($stale);
            $this->fail('The changed work state must invalidate the operation.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $stale->fresh()->getRawOriginal());
            $this->assertSame($activityCount + 1, Activity::forSubject($stale)->count());
            $attempt = Activity::forSubject($stale)->where('event', 'action_attempted')->sole();
            $this->assertSame($action, $attempt->getProperty('action'));
        }
    }

    #[DataProvider('ownershipActions')]
    public function test_former_owner_cannot_mutate_a_reassigned_work(string $action, bool $trashed): void
    {
        $owner = User::factory()->create();
        $owner->givePermissionTo([Permissions::WORK_EDIT_OWN, Permissions::WORK_RESTORE_OWN]);
        $this->actingAs($owner);
        $stale = Work::factory()->for($owner)->create(['deleted_at' => $trashed ? now() : null]);
        $data = $this->data($stale);
        Work::withTrashed()->whereKey($stale->id)->update(['user_id' => User::factory()->create()->id]);
        $before = $stale->fresh()->getRawOriginal();

        try {
            if ($action === UpdateWork::class) {
                app($action)->handle($stale, $data);
            } else {
                app($action)->handle($stale);
            }
            $this->fail('The former owner must lose permission.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $stale->fresh()->getRawOriginal());
        }
    }

    #[DataProvider('lifecycleActions')]
    public function test_activity_failure_rolls_back_the_transition_and_all_activity(string $action, WorkStatus $initial, string $description): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $work = Work::factory()->create(['status' => $initial]);
        $before = $work->refresh()->getRawOriginal();
        $activityCount = Activity::forSubject($work)->count();
        Activity::creating(function (Activity $activity) use ($description): void {
            if ($activity->description === $description) {
                throw new RuntimeException('Could not save transition activity.');
            }
        });

        try {
            app($action)->handle($work);
            $this->fail('Activity failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Could not save transition activity.', $exception->getMessage());
            $this->assertSame($before, $work->fresh()->getRawOriginal());
            $this->assertSame($activityCount + 1, Activity::forSubject($work)->count());
            $attempt = Activity::forSubject($work)->where('event', 'action_attempted')->sole();
            $this->assertSame($action, $attempt->getProperty('action'));
        }
    }

    public function test_update_returns_the_same_refreshed_instance(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $work = Work::factory()->create();

        $result = app(UpdateWork::class)->handle($work, $this->data($work));

        $this->assertSame($work, $result);
        $this->assertSame('Updated notes', $work->fresh()->notes);
    }

    public function test_update_does_not_recreate_a_permanently_deleted_work(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $stale = Work::factory()->create();
        $data = $this->data($stale);
        Work::whereKey($stale->id)->forceDelete();

        $this->expectException(ModelNotFoundException::class);

        app(UpdateWork::class)->handle($stale, $data);
    }

    private function data(Work $work): WorkDto
    {
        return new WorkDto(
            userId: $work->user_id, quoteId: null, orderId: $work->order_id,
            typeOfProduct: null, typeNumber: null, serialNumber: null,
            warrantyAccepted: false, repairInWarranty: null, dayOfPurchase: null,
            buildingType: null, reportedProblem: null, workAcceptedAt: null,
            workStartedAt: null, workCompletedAt: null, workPerformedDescription: null,
            transportFrom: null, transportTo: null, transportRateInCzk: null,
            transportDistanceInKm: null, notes: 'Updated notes', mechanicName: null,
        );
    }
}
