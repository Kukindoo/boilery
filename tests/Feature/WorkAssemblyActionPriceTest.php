<?php

namespace Tests\Feature;

use App\Actions\AssemblyActions\UpdateWorkAssemblyActionPrice;
use App\Enums\Permissions;
use App\Enums\Roles;
use App\Livewire\AssemblyActions\WorkAssemblyActionAdd;
use App\Livewire\AssemblyActions\WorkAssemblyActionRow;
use App\Livewire\AssemblyActions\WorkAssemblyActions;
use App\Models\AssemblyAction;
use App\Models\User;
use App\Models\Work;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class WorkAssemblyActionPriceTest extends FeatureTestCase
{
    public function test_add_requires_a_price_and_stores_it_on_each_work_link(): void
    {
        $action = AssemblyAction::factory()->create();
        $work = Work::factory()->create();
        $otherWork = Work::factory()->create();
        $otherWork->assemblyActions()->attach($action, ['price_in_cents' => 5000]);
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkAssemblyActionAdd::class, ['work' => $work])
            ->set('form.assemblyActionId', $action->id)->call('submit')
            ->assertHasErrors(['form.price' => 'required']);
        $this->assertDatabaseMissing('assembly_action_work', ['work_id' => $work->id]);
        $component->set('form.price', '17,45')->call('submit')->assertHasNoErrors()
            ->assertDispatched('assembly-action-added')->assertSet('form.price', '');
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $work->id, 'assembly_action_id' => $action->id, 'price_in_cents' => 1745]);
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $otherWork->id, 'assembly_action_id' => $action->id, 'price_in_cents' => 5000]);
        $this->assertSame(1745, (int) $action->works()->findOrFail($work->id)->pivot->getAttribute('price_in_cents'));
    }

    public function test_price_edit_updates_only_its_work_link_and_total(): void
    {
        $work = Work::factory()->create();
        $otherWork = Work::factory()->create();
        $action = AssemblyAction::factory()->create();
        $second = AssemblyAction::factory()->create();
        $work->assemblyActions()->attach($action, ['price_in_cents' => 1745]);
        $work->assemblyActions()->attach($second, ['price_in_cents' => 1000]);
        $otherWork->assemblyActions()->attach($action, ['price_in_cents' => 5000]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkAssemblyActionRow::class, ['work' => $work, 'action' => $action, 'priceInCents' => 1745])
            ->call('edit')->assertSet('form.price', '17,45')->assertSet('editing', true)
            ->set('form.price', '23.50')->call('submit')->assertHasNoErrors()
            ->assertSet('editing', false)->assertSee('CZK 23,50')->assertDispatched('assembly-actions-changed');

        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $work->id, 'assembly_action_id' => $action->id, 'price_in_cents' => 2350]);
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $otherWork->id, 'assembly_action_id' => $action->id, 'price_in_cents' => 5000]);
        Livewire::test(WorkAssemblyActions::class, ['work' => $work])->assertSee('CZK 33,50');
        $activity = Activity::inLog('assembly_actions')->where('event', 'action_completed')->sole();
        $this->assertSame(UpdateWorkAssemblyActionPrice::class, $activity->getProperty('action'));
        $this->assertSame(1745, $activity->getProperty('old_price_in_cents'));
        $this->assertSame(2350, $activity->getProperty('price_in_cents'));
    }

    #[TestWith([''])]
    #[TestWith(['-1'])]
    #[TestWith(['1.234'])]
    #[TestWith(['42949672,96'])]
    #[TestWith(['abc'])]
    public function test_invalid_prices_preserve_pivot_and_keep_editor_open(string $price): void
    {
        $work = Work::factory()->create();
        $action = AssemblyAction::factory()->create();
        $work->assemblyActions()->attach($action, ['price_in_cents' => 1745]);
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkAssemblyActionRow::class, ['work' => $work, 'action' => $action, 'priceInCents' => 1745])
            ->call('edit')->set('form.price', $price)->call('submit')
            ->assertHasErrors('form.price')->assertSet('editing', true)
            ->assertSet('form.price', $price)->assertNotDispatched('assembly-actions-changed');
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $work->id, 'price_in_cents' => 1745]);
    }

    public function test_zero_price_preserves_the_link_and_cancel_discards_edits(): void
    {
        $work = Work::factory()->create();
        $action = AssemblyAction::factory()->create();
        $work->assemblyActions()->attach($action, ['price_in_cents' => 1745]);
        $row = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkAssemblyActionRow::class, ['work' => $work, 'action' => $action, 'priceInCents' => 1745]);
        $row->call('edit')->set('form.price', '99')->call('cancel')
            ->assertSet('editing', false)->assertSet('form.price', '17,45')->assertNotDispatched('assembly-actions-changed');
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $work->id, 'price_in_cents' => 1745]);
        $row->call('edit')->set('form.price', '0')->call('submit')->assertHasNoErrors()->assertSee('CZK 0,00');
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $work->id, 'assembly_action_id' => $action->id, 'price_in_cents' => 0]);
    }

    #[TestWith(['edit'])]
    #[TestWith(['cancel'])]
    #[TestWith(['submit'])]
    #[TestWith(['confirmDelete'])]
    #[TestWith(['delete'])]
    public function test_row_actions_recheck_edit_permission(string $method): void
    {
        $admin = User::factory()->admin()->create();
        $work = Work::factory()->create();
        $action = AssemblyAction::factory()->create();
        $work->assemblyActions()->attach($action, ['price_in_cents' => 1745]);
        $row = Livewire::actingAs($admin)
            ->test(WorkAssemblyActionRow::class, ['work' => $work, 'action' => $action, 'priceInCents' => 1745])
            ->call('edit')->set('form.price', '25');
        Role::findById(Roles::ADMIN->value)->revokePermissionTo([
            Permissions::WORK_EDIT_ALL->label(), Permissions::WORK_EDIT_OWN->label(),
        ]);
        Livewire::actingAs($admin->fresh());
        $row->call($method)->assertForbidden();
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $work->id, 'price_in_cents' => 1745]);
    }

    public function test_saving_does_not_recreate_a_removed_link(): void
    {
        $work = Work::factory()->create();
        $action = AssemblyAction::factory()->create();
        $work->assemblyActions()->attach($action, ['price_in_cents' => 1745]);
        $row = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkAssemblyActionRow::class, ['work' => $work, 'action' => $action, 'priceInCents' => 1745])->call('edit');
        $work->assemblyActions()->detach($action);
        $row->set('form.price', '25')->call('submit')->assertNotFound();
        $this->assertDatabaseMissing('assembly_action_work', ['work_id' => $work->id]);
    }

    public function test_customer_can_read_own_work_actions_and_prices_without_catalog_or_edit_access(): void
    {
        $customer = User::factory()->create()->assignRole(Roles::CUSTOMER->value);
        $customer->givePermissionTo(Permissions::WORK_VIEW_OWN->label());
        $work = Work::factory()->for($customer)->create();
        $otherWork = Work::factory()->create();
        $action = AssemblyAction::factory()->create(['name' => 'Kontrola kotle']);
        $work->assemblyActions()->attach($action, ['price_in_cents' => 1745]);

        $this->actingAs($customer)->get(route('works.show', $work))->assertOk()
            ->assertSeeLivewire(WorkAssemblyActions::class)->assertSee('Kontrola kotle')->assertSee('CZK 17,45')
            ->assertDontSee(route('assembly-actions.show', $action), false)
            ->assertDontSeeLivewire(WorkAssemblyActionAdd::class)->assertDontSee('Upravit cenu');
        $this->get(route('works.show', $otherWork))->assertForbidden();
        Livewire::test(WorkAssemblyActions::class, ['work' => $otherWork])->assertForbidden();
        Livewire::test(WorkAssemblyActionRow::class, ['work' => $work, 'action' => $action, 'priceInCents' => 1745])
            ->call('edit')->assertForbidden();
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $work->id, 'price_in_cents' => 1745]);
    }

    public function test_shared_deletion_modal_can_cancel_and_then_remove_only_the_work_link(): void
    {
        $work = Work::factory()->create();
        $otherWork = Work::factory()->create();
        $action = AssemblyAction::factory()->create();
        $work->assemblyActions()->attach($action, ['price_in_cents' => 1745]);
        $otherWork->assemblyActions()->attach($action, ['price_in_cents' => 2500]);
        $row = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkAssemblyActionRow::class, ['work' => $work, 'action' => $action, 'priceInCents' => 1745]);

        $row->call('confirmDelete')->assertSet('confirmingDeletion', true)->assertSee('Odebrat pracovní úkon?')
            ->set('confirmingDeletion', false)->assertNotDispatched('assembly-actions-changed');
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $work->id, 'assembly_action_id' => $action->id]);

        $row->call('confirmDelete')->call('delete')->assertHasNoErrors()
            ->assertSet('confirmingDeletion', false)->assertDispatched('assembly-actions-changed');
        $this->assertDatabaseMissing('assembly_action_work', ['work_id' => $work->id, 'assembly_action_id' => $action->id]);
        $this->assertDatabaseHas('assembly_action_work', ['work_id' => $otherWork->id, 'assembly_action_id' => $action->id, 'price_in_cents' => 2500]);
        $this->assertNotSoftDeleted($action);
    }
}
