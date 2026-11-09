<?php

namespace Tests\Feature;

use App\Actions\AssemblyActions\DeleteAssemblyAction;
use App\Actions\AssemblyActions\ForceDeleteAssemblyAction;
use App\Livewire\AssemblyActions\AssemblyActionsTable;
use App\Livewire\Modals\Deletion;
use App\Models\AssemblyAction;
use App\Models\User;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;

class AssemblyActionDeletionTest extends FeatureTestCase
{
    #[TestWith([false])]
    #[TestWith([true])]
    public function test_used_actions_cannot_be_deleted_even_when_the_work_is_archived(bool $archived): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $action = AssemblyAction::factory()->create();
        $work = Work::factory()->create();
        $work->assemblyActions()->attach($action, ['price_in_cents' => 1745]);
        if ($archived) {
            $work->delete();
        }

        $this->assertFalse(Gate::allows('delete', $action));
        Livewire::test(AssemblyActionsTable::class)->assertDontSeeLivewire(Deletion::class);
        Livewire::test(Deletion::class, ['model' => $action, 'heading' => 'Smazat pracovní úkon?', 'text' => 'Potvrďte odstranění.'])->call('confirmDelete')->assertForbidden();

        try {
            app(DeleteAssemblyAction::class)->handle($action);
            $this->fail('Used assembly actions must not be deleted.');
        } catch (AuthorizationException) {
            $this->assertFalse($action->fresh()->trashed());
        }
        $this->assertDatabaseHas('assembly_action_work', [
            'work_id' => $work->id, 'assembly_action_id' => $action->id, 'price_in_cents' => 1745,
        ]);
    }

    public function test_delete_rechecks_usage_after_confirmation(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $action = AssemblyAction::factory()->create();
        $component = Livewire::test(Deletion::class, ['model' => $action, 'heading' => 'Smazat pracovní úkon?', 'text' => 'Potvrďte odstranění.'])->call('confirmDelete');
        Work::factory()->create()->assemblyActions()->attach($action);

        $component->call('delete')->assertForbidden();
        $this->assertFalse($action->fresh()->trashed());
        $this->assertDatabaseHas('assembly_action_work', ['assembly_action_id' => $action->id]);
    }

    public function test_unused_action_can_be_deleted_after_its_work_link_is_removed(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $action = AssemblyAction::factory()->create();
        $work = Work::factory()->create();
        $work->assemblyActions()->attach($action);
        $this->assertFalse(Gate::allows('delete', $action));

        $work->assemblyActions()->detach($action);
        Livewire::test(AssemblyActionsTable::class)->assertSeeLivewire(Deletion::class);
        Livewire::test(Deletion::class, ['model' => $action, 'heading' => 'Smazat pracovní úkon?', 'text' => 'Potvrďte odstranění.'])->call('confirmDelete')->assertSet('show', true)
            ->call('delete')->assertHasNoErrors()
            ->assertSet('show', false);
        $this->assertSoftDeleted($action);
    }

    public function test_used_archived_action_cannot_be_permanently_deleted(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $work = Work::factory()->create();
        $action = AssemblyAction::factory()->create();
        $work->assemblyActions()->attach($action);
        $action->delete();

        $this->assertFalse(Gate::allows('forceDelete', $action));
        try {
            app(ForceDeleteAssemblyAction::class)->handle($action);
            $this->fail('Used assembly actions must not be permanently deleted.');
        } catch (AuthorizationException) {
            $this->assertSoftDeleted($action);
        }
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $work->id, 'assembly_action_id' => $action->id]);
    }

    public function test_delete_action_rechecks_usage_on_a_model_with_cached_unused_status(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $action = AssemblyAction::factory()->create();
        $cached = AssemblyAction::query()->withExists('works')->findOrFail($action->id);
        $this->assertTrue(Gate::allows('delete', $cached));
        Work::factory()->create()->assemblyActions()->attach($action);

        try {
            app(DeleteAssemblyAction::class)->handle($cached);
            $this->fail('Deletion must refresh the cached unused status.');
        } catch (AuthorizationException) {
            $this->assertFalse($action->fresh()->trashed());
        }
    }
}
