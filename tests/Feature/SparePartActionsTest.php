<?php

namespace Tests\Feature;

use App\Actions\SpareParts\AttachSparePartToWork;
use App\Actions\SpareParts\CreateSparePart;
use App\Actions\SpareParts\DeleteSparePart;
use App\Actions\SpareParts\DetachSparePartFromWork;
use App\Actions\SpareParts\ForceDeleteSparePart;
use App\Actions\SpareParts\RestoreSparePart;
use App\Actions\SpareParts\UpdateSparePart;
use App\Actions\SpareParts\UpdateWorkSparePartAmount;
use App\Dtos\SpareParts\SparePartDto;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class SparePartActionsTest extends FeatureTestCase
{
    #[TestWith([CreateSparePart::class])]
    #[TestWith([UpdateSparePart::class])]
    #[TestWith([DeleteSparePart::class])]
    #[TestWith([RestoreSparePart::class])]
    #[TestWith([ForceDeleteSparePart::class])]
    #[TestWith([AttachSparePartToWork::class])]
    #[TestWith([DetachSparePartFromWork::class])]
    #[TestWith([UpdateWorkSparePartAmount::class])]
    public function test_actions_log_completion_with_actor_subject_and_relationship_context(string $action): void
    {
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor);
        $part = SparePart::factory()->create();
        $work = Work::factory()->create();
        $this->prepare($action, $part, $work);

        $result = $this->execute($action, $part, $work);

        $entry = Activity::inLog('spare_parts')->where('event', 'action_completed')->sole();
        $this->assertSame($action, $entry->getProperty('action'));
        $this->assertSame($actor->id, $entry->causer_id);
        $this->assertSame($result->id, $entry->subject_id);
        $this->assertSame($part->getMorphClass(), $entry->subject_type);
        if (in_array($action, [AttachSparePartToWork::class, DetachSparePartFromWork::class], true)) {
            $this->assertSame($work->id, $entry->getProperty('work_id'));
        }
    }

    #[TestWith([CreateSparePart::class])]
    #[TestWith([UpdateSparePart::class])]
    #[TestWith([DeleteSparePart::class])]
    #[TestWith([RestoreSparePart::class])]
    #[TestWith([ForceDeleteSparePart::class])]
    #[TestWith([AttachSparePartToWork::class])]
    #[TestWith([DetachSparePartFromWork::class])]
    #[TestWith([UpdateWorkSparePartAmount::class])]
    public function test_completion_logging_failure_rolls_back_writes_and_records_failed_attempt(string $action): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $part = SparePart::factory()->create();
        $work = Work::factory()->create();
        $this->prepare($action, $part, $work);
        $before = $part->refresh()->getRawOriginal();
        $linked = $work->spareParts()->count();
        $beforeAmounts = $work->spareParts()->get()->mapWithKeys(fn (SparePart $part): array => [$part->id => (int) $part->pivot->amount])->all();
        $failure = new RuntimeException('Activity could not be stored.');
        Activity::creating(function (Activity $activity) use ($failure): void {
            if ($activity->log_name === 'spare_parts' && $activity->event === 'action_completed') {
                throw $failure;
            }
        });

        try {
            $this->execute($action, $part, $work);
            $this->fail('The action must propagate the logging failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertSame($before, SparePart::withTrashed()->findOrFail($part->id)->getRawOriginal());
        $this->assertSame($linked, $work->spareParts()->count());
        $this->assertSame($beforeAmounts, $work->spareParts()->get()->mapWithKeys(fn (SparePart $part): array => [$part->id => (int) $part->pivot->amount])->all());
        $this->assertSame(1, SparePart::withTrashed()->count());
        $this->assertSame(0, Activity::inLog('spare_parts')->where('event', 'action_completed')->count());
        $attempt = Activity::inLog('spare_parts')->where('event', 'action_attempted')->sole();
        $this->assertSame($action, $attempt->getProperty('action'));
        $this->assertSame(RuntimeException::class, $attempt->getProperty('exception'));
    }

    #[TestWith([UpdateSparePart::class, false])]
    #[TestWith([DeleteSparePart::class, false])]
    #[TestWith([RestoreSparePart::class, true])]
    #[TestWith([ForceDeleteSparePart::class, true])]
    public function test_actions_reauthorize_current_state_instead_of_stale_models(string $action, bool $initiallyDeleted): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $part = SparePart::factory()->create();
        $work = Work::factory()->create();
        if ($initiallyDeleted) {
            $part->delete();
        }
        $stale = clone $part;
        $part->deleted_at = $initiallyDeleted ? null : now();
        $part->save();
        $before = $part->refresh()->getRawOriginal();

        try {
            $this->execute($action, $stale, $work);
            $this->fail('The action must reject the changed state.');
        } catch (AuthorizationException) {
            $this->assertSame($before, SparePart::withTrashed()->findOrFail($part->id)->getRawOriginal());
        }
        $attempt = Activity::inLog('spare_parts')->where('event', 'action_attempted')->sole();
        $this->assertSame($action, $attempt->getProperty('action'));
    }

    private function prepare(string $action, SparePart $part, Work $work): void
    {
        if (in_array($action, [RestoreSparePart::class, ForceDeleteSparePart::class], true)) {
            $part->delete();
        }
        if (in_array($action, [DetachSparePartFromWork::class, UpdateWorkSparePartAmount::class], true)) {
            $work->spareParts()->attach($part);
        }
    }

    private function execute(string $action, SparePart $part, Work $work): SparePart
    {
        $data = new SparePartDto('Updated part', 'SP-42', null, 1745);
        switch ($action) {
            case CreateSparePart::class:
                return app(CreateSparePart::class)->handle($data);
            case UpdateSparePart::class:
                return app(UpdateSparePart::class)->handle($part, $data);
            case UpdateWorkSparePartAmount::class:
                app(UpdateWorkSparePartAmount::class)->handle($work, $part, 5);
                break;
            case AttachSparePartToWork::class:
            case DetachSparePartFromWork::class:
                app($action)->handle($work, $part);
                break;
            default:
                app($action)->handle($part);
        }

        return $part;
    }
}
