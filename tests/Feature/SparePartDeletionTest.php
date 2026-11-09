<?php

namespace Tests\Feature;

use App\Actions\SpareParts\DeleteSparePart;
use App\Actions\SpareParts\ForceDeleteSparePart;
use App\Livewire\SpareParts\SparePartsTable;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;

class SparePartDeletionTest extends FeatureTestCase
{
    #[TestWith([false])]
    #[TestWith([true])]
    public function test_used_actions_cannot_be_deleted_even_when_the_work_is_archived(bool $archived): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $part = SparePart::factory()->create();
        $work = Work::factory()->create();
        $work->spareParts()->attach($part, ['amount' => 2]);
        if ($archived) {
            $work->delete();
        }

        $this->assertFalse(Gate::allows('delete', $part));
        Livewire::test(SparePartsTable::class)
            ->assertDontSee('wire:click="confirmDelete(' . $part->id . ')"', false)
            ->call('confirmDelete', $part->id)->assertForbidden();

        try {
            app(DeleteSparePart::class)->handle($part);
            $this->fail('Used spare parts must not be deleted.');
        } catch (AuthorizationException) {
            $this->assertFalse($part->fresh()->trashed());
        }
        $this->assertDatabaseHas('spare_part_work', [
            'work_id' => $work->id, 'spare_part_id' => $part->id, 'amount' => 2,
        ]);
    }

    public function test_delete_rechecks_usage_after_confirmation(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $part = SparePart::factory()->create();
        $component = Livewire::test(SparePartsTable::class)->call('confirmDelete', $part->id);
        Work::factory()->create()->spareParts()->attach($part);

        $component->call('delete')->assertForbidden();
        $this->assertFalse($part->fresh()->trashed());
        $this->assertDatabaseHas('spare_part_work', ['spare_part_id' => $part->id]);
    }

    public function test_unused_action_can_be_deleted_after_its_work_link_is_removed(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $part = SparePart::factory()->create();
        $work = Work::factory()->create();
        $work->spareParts()->attach($part);
        $this->assertFalse(Gate::allows('delete', $part));

        $work->spareParts()->detach($part);
        Livewire::test(SparePartsTable::class)
            ->assertSee('wire:click="confirmDelete(' . $part->id . ')"', false)
            ->call('confirmDelete', $part->id)->call('delete')->assertHasNoErrors();
        $this->assertSoftDeleted($part);
    }

    public function test_used_archived_action_cannot_be_permanently_deleted(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        $work->spareParts()->attach($part);
        $part->delete();

        $this->assertFalse(Gate::allows('forceDelete', $part));
        try {
            app(ForceDeleteSparePart::class)->handle($part);
            $this->fail('Used spare parts must not be permanently deleted.');
        } catch (AuthorizationException) {
            $this->assertSoftDeleted($part);
        }
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id]);
    }

    public function test_delete_action_rechecks_usage_on_a_model_with_cached_unused_status(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $part = SparePart::factory()->create();
        $cached = SparePart::query()->withExists('works')->findOrFail($part->id);
        $this->assertTrue(Gate::allows('delete', $cached));
        Work::factory()->create()->spareParts()->attach($part);

        try {
            app(DeleteSparePart::class)->handle($cached);
            $this->fail('Deletion must refresh the cached unused status.');
        } catch (AuthorizationException) {
            $this->assertFalse($part->fresh()->trashed());
        }
    }

    public function test_cancel_closes_shared_modal_without_deleting_the_part(): void
    {
        Livewire::actingAs(User::factory()->admin()->create());
        $part = SparePart::factory()->create();
        Livewire::test(SparePartsTable::class)
            ->call('confirmDelete', $part->id)->assertSet('confirmingDeletion', true)
            ->assertSee('Smazat náhradní díl?')
            ->set('confirmingDeletion', false)->assertSet('confirmingDeletion', false);
        $this->assertNotSoftDeleted($part);
    }

    public function test_restoring_a_legacy_archived_part_preserves_its_existing_work_link(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $work = Work::factory()->create();
        $part = SparePart::factory()->create();
        $work->spareParts()->attach($part, ['amount' => 3]);
        $part->delete();

        app(\App\Actions\SpareParts\RestoreSparePart::class)->handle($part);
        $this->assertFalse($part->fresh()->trashed());
        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $part->id, 'amount' => 3]);
    }
}
