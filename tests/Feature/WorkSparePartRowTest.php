<?php

namespace Tests\Feature;

use App\Actions\SpareParts\DetachSparePartFromWork;
use App\Actions\SpareParts\UpdateWorkSparePartAmount;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\SpareParts\WorkSparePartRow;
use App\Livewire\SpareParts\WorkSpareParts;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class WorkSparePartRowTest extends FeatureTestCase
{
    public function test_editing_amount_updates_only_its_work_and_recalculates_totals(): void
    {
        $work = Work::factory()->create();
        $otherWork = Work::factory()->create();
        $part = SparePart::factory()->create(['price_in_cents' => 1745]);
        $work->spareParts()->attach($part, ['amount' => 2]);
        $otherWork->spareParts()->attach($part, ['amount' => 10]);
        $otherPart = SparePart::factory()->create(['price_in_cents' => 1500]);
        $work->spareParts()->attach($otherPart, ['amount' => 1]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkSparePartRow::class, ['work' => $work, 'part' => $part, 'amount' => 2])
            ->call('edit')->assertSet('editing', true)->assertSet('form.amount', '2')
            ->set('form.amount', '3')->call('submit')->assertHasNoErrors()
            ->assertSet('editing', false)->assertSee('CZK 52,35')->assertDispatched('spare-parts-changed');

        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id, 'amount' => 3]);
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $otherWork->id, 'spare_part_id' => $part->id, 'amount' => 10]);
        Livewire::test(WorkSpareParts::class, ['work' => $work])->assertSee('CZK 67,35');
        $entry = Activity::inLog('spare_parts')->where('event', 'action_completed')->sole();
        $this->assertSame(UpdateWorkSparePartAmount::class, $entry->getProperty('action'));
        $this->assertSame(2, $entry->getProperty('old_amount'));
        $this->assertSame(3, $entry->getProperty('amount'));
    }

    public function test_zero_amount_removes_only_the_current_work_link_and_refreshes_totals(): void
    {
        $work = Work::factory()->create();
        $otherWork = Work::factory()->create();
        $part = SparePart::factory()->create(['price_in_cents' => 1745]);
        $work->spareParts()->attach($part, ['amount' => 2]);
        $otherWork->spareParts()->attach($part, ['amount' => 5]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkSparePartRow::class, ['work' => $work, 'part' => $part, 'amount' => 2])
            ->call('edit')->set('form.amount', '0')->call('submit')
            ->assertHasNoErrors()->assertDispatched('spare-parts-changed');

        $this->assertDatabaseMissing('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id]);
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $otherWork->id, 'spare_part_id' => $part->id, 'amount' => 5]);
        $this->assertModelExists($part);
        Livewire::test(WorkSpareParts::class, ['work' => $work])
            ->assertSee('CZK 0,00')->assertDontSeeLivewire(WorkSparePartRow::class);
        $activity = Activity::inLog('spare_parts')->where('event', 'action_completed')->sole();
        $this->assertSame('Spare part detached from work', $activity->description);
        $this->assertSame(DetachSparePartFromWork::class, $activity->getProperty('action'));
        $this->assertSame(0, $activity->getProperty('amount'));
        $this->assertSame(2, $activity->getProperty('old_amount'));
    }

    public function test_zero_amount_removal_rolls_back_when_activity_logging_fails(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        $work->spareParts()->attach($part, ['amount' => 2]);
        $failure = new RuntimeException('Could not log removal.');
        Activity::creating(function (Activity $activity) use ($failure): void {
            if ($activity->log_name === 'spare_parts' && $activity->event === 'action_completed') {
                throw $failure;
            }
        });

        try {
            app(UpdateWorkSparePartAmount::class)->handle($work, $part, 0);
            $this->fail('Logging failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id, 'amount' => 2]);
        $this->assertSame(1, Activity::inLog('spare_parts')->where('event', 'action_attempted')->count());
    }

    #[TestWith(['-1'])]
    #[TestWith(['1.5'])]
    #[TestWith([''])]
    #[TestWith(['65536'])]
    public function test_invalid_amount_keeps_editor_open_without_changing_pivot(string $amount): void
    {
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        $work->spareParts()->attach($part, ['amount' => 2]);
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkSparePartRow::class, ['work' => $work, 'part' => $part, 'amount' => 2])
            ->call('edit')->set('form.amount', $amount)->call('submit')
            ->assertHasErrors('form.amount')->assertSet('editing', true)
            ->assertSet('form.amount', $amount)->assertNotDispatched('spare-parts-changed');
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id, 'amount' => 2]);
    }

    public function test_cancel_restores_saved_amount_without_writing(): void
    {
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        $work->spareParts()->attach($part, ['amount' => 2]);
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkSparePartRow::class, ['work' => $work, 'part' => $part, 'amount' => 2])
            ->call('edit')->set('form.amount', '8')->call('cancel')
            ->assertSet('editing', false)->assertSet('form.amount', '2')->assertNotDispatched('spare-parts-changed');
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id, 'amount' => 2]);
    }

    #[TestWith(['edit'])]
    #[TestWith(['cancel'])]
    #[TestWith(['submit'])]
    #[TestWith(['delete'])]
    #[TestWith(['confirmDelete'])]
    public function test_row_actions_recheck_permissions_after_opening_editor(string $method): void
    {
        $admin = User::factory()->admin()->create();
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        $work->spareParts()->attach($part, ['amount' => 2]);
        $row = Livewire::actingAs($admin)
            ->test(WorkSparePartRow::class, ['work' => $work, 'part' => $part, 'amount' => 2])
            ->call('edit')->set('form.amount', '3');
        Role::findById(Roles::ADMIN->value)->revokePermissionTo([
            Permissions::WORK_EDIT_ALL->label(), Permissions::WORK_EDIT_OWN->label(),
        ]);
        Livewire::actingAs($admin->fresh());
        $row->call($method)->assertForbidden();
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id, 'amount' => 2]);
    }

    public function test_removal_confirmation_can_be_cancelled_before_detaching_only_this_work(): void
    {
        $work = Work::factory()->create();
        $otherWork = Work::factory()->create();
        $part = SparePart::factory()->create();
        $work->spareParts()->attach($part, ['amount' => 2]);
        $otherWork->spareParts()->attach($part, ['amount' => 3]);
        $row = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkSparePartRow::class, ['work' => $work, 'part' => $part, 'amount' => 2])
            ->assertSeeLivewire(\App\Livewire\Modals\Deletion::class)
            ->assertDontSee('wire:confirm', false);

        $row->call('confirmDelete')->assertSet('confirmingDeletion', true)
            ->set('confirmingDeletion', false)->assertNotDispatched('spare-parts-changed');
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id]);

        $row->call('confirmDelete')->call('delete')->assertSet('confirmingDeletion', false)
            ->assertDispatched('spare-parts-changed');
        $this->assertDatabaseMissing('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id]);
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $otherWork->id, 'spare_part_id' => $part->id, 'amount' => 3]);
        $this->assertNotSoftDeleted($part);
    }

    public function test_save_does_not_recreate_a_link_removed_after_opening_editor(): void
    {
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        $work->spareParts()->attach($part, ['amount' => 2]);
        $row = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkSparePartRow::class, ['work' => $work, 'part' => $part, 'amount' => 2])->call('edit');
        $work->spareParts()->detach($part);
        $row->set('form.amount', '3')->call('submit')->assertNotFound();
        $this->assertDatabaseMissing('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id]);
    }
}
