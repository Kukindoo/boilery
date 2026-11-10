<?php

namespace Tests\Feature;

use App\Actions\AssemblyActions\AttachAssemblyActionToWork;
use App\Actions\AssemblyActions\DetachAssemblyActionFromWork;
use App\Actions\SpareParts\AttachSparePartToWork;
use App\Actions\SpareParts\DetachSparePartFromWork;
use App\Livewire\AssemblyActions\WorkAssemblyActionAdd;
use App\Livewire\SpareParts\WorkSparePartAdd;
use App\Models\AssemblyAction;
use App\Models\SparePart;
use App\Models\User;
use App\Models\Work;
use App\ValueObjects\Price;
use Livewire\Livewire;

class WorkItemLimitsTest extends FeatureTestCase
{
    public function test_add_part_control_hides_at_the_limit_and_returns_after_removal(): void
    {
        $work = Work::factory()->create();
        $parts = SparePart::factory()->count(4)->create();
        $work->spareParts()->attach($parts->modelKeys(), ['amount' => 1]);
        $lastPart = SparePart::factory()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(\App\Livewire\SpareParts\WorkSpareParts::class, ['work' => $work])
            ->assertSee('Přidat díl');

        $work->spareParts()->attach($lastPart, ['amount' => 1]);
        $component->call('$refresh')->assertDontSee('Přidat díl');

        $work->spareParts()->detach($lastPart);
        $component->call('$refresh')->assertSee('Přidat díl');
    }

    public function test_add_action_control_hides_at_the_limit_and_returns_after_removal(): void
    {
        $work = Work::factory()->create();
        $actions = AssemblyAction::factory()->count(3)->create();
        $work->assemblyActions()->attach($actions->modelKeys(), ['price_in_cents' => 100]);
        $lastAction = AssemblyAction::factory()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(\App\Livewire\AssemblyActions\WorkAssemblyActions::class, ['work' => $work])
            ->assertSee('Přidat úkon');

        $work->assemblyActions()->attach($lastAction, ['price_in_cents' => 100]);
        $component->call('$refresh')->assertDontSee('Přidat úkon');

        $work->assemblyActions()->detach($lastAction);
        $component->call('$refresh')->assertSee('Přidat úkon');
    }

    public function test_fifth_part_is_allowed_but_sixth_requires_removing_an_existing_part(): void
    {
        $work = Work::factory()->create();
        $parts = SparePart::factory()->count(4)->create();
        $work->spareParts()->attach($parts->modelKeys(), ['amount' => 1]);
        $fifth = SparePart::factory()->create();
        $sixth = SparePart::factory()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkSparePartAdd::class, ['work' => $work]);

        $component->set('form.sparePartId', $fifth->id)->set('form.amount', '12')
            ->call('submit')->assertHasNoErrors()->assertDispatched('spare-part-added');

        $this->assertDatabaseHas('spare_part_work', [
            'work_id' => $work->id, 'spare_part_id' => $fifth->id, 'amount' => 12,
        ]);

        $component->set('form.sparePartId', $sixth->id)->call('submit')
            ->assertHasErrors('form.sparePartId')
            ->assertSee('K zakázce lze přidat nejvýše 5 náhradních dílů.');

        $this->assertSame(5, $work->spareParts()->count());
        $this->assertDatabaseMissing('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $sixth->id]);

        app(DetachSparePartFromWork::class)->handle($work, $fifth);
        $component->call('submit')->assertHasNoErrors();

        $this->assertDatabaseHas('spare_part_work', ['work_id' => $work->id, 'spare_part_id' => $sixth->id]);
        $this->assertSame(5, $work->spareParts()->count());
    }

    public function test_fourth_action_is_allowed_but_fifth_requires_removing_an_existing_action(): void
    {
        $work = Work::factory()->create();
        $actions = AssemblyAction::factory()->count(3)->create();
        $work->assemblyActions()->attach($actions->modelKeys(), ['price_in_cents' => 100]);
        $fourth = AssemblyAction::factory()->create();
        $fifth = AssemblyAction::factory()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())
            ->test(WorkAssemblyActionAdd::class, ['work' => $work]);

        $component->set('form.assemblyActionId', $fourth->id)->set('form.price', '25')
            ->call('submit')->assertHasNoErrors()->assertDispatched('assembly-action-added');

        $this->assertDatabaseHas('assembly_action_work', [
            'work_id' => $work->id, 'assembly_action_id' => $fourth->id, 'price_in_cents' => 2500,
        ]);

        $component->set('form.assemblyActionId', $fifth->id)->set('form.price', '30')->call('submit')
            ->assertHasErrors('form.assemblyActionId')
            ->assertSee('K zakázce lze přidat nejvýše 4 pracovní úkony.');

        $this->assertSame(4, $work->assemblyActions()->count());
        $this->assertDatabaseMissing('assembly_action_work', ['work_id' => $work->id, 'assembly_action_id' => $fifth->id]);

        app(DetachAssemblyActionFromWork::class)->handle($work, $fourth);
        $component->call('submit')->assertHasNoErrors();

        $this->assertDatabaseHas('assembly_action_work', [
            'work_id' => $work->id, 'assembly_action_id' => $fifth->id, 'price_in_cents' => 3000,
        ]);
        $this->assertSame(4, $work->assemblyActions()->count());
    }

    public function test_existing_part_amount_can_be_updated_at_the_limit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $work = Work::factory()->create();
        $parts = SparePart::factory()->count(5)->create();
        $work->spareParts()->attach($parts->modelKeys(), ['amount' => 1]);

        app(AttachSparePartToWork::class)->handle($work, $parts->first(), 20);

        $this->assertSame(5, $work->spareParts()->count());
        $this->assertDatabaseHas('spare_part_work', [
            'work_id' => $work->id, 'spare_part_id' => $parts->first()->id, 'amount' => 20,
        ]);
    }

    public function test_existing_action_price_can_be_updated_at_the_limit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $work = Work::factory()->create();
        $actions = AssemblyAction::factory()->count(4)->create();
        $work->assemblyActions()->attach($actions->modelKeys(), ['price_in_cents' => 100]);

        app(AttachAssemblyActionToWork::class)->handle($work, $actions->first(), Price::fromInput('25'));

        $this->assertSame(4, $work->assemblyActions()->count());
        $this->assertDatabaseHas('assembly_action_work', [
            'work_id' => $work->id, 'assembly_action_id' => $actions->first()->id, 'price_in_cents' => 2500,
        ]);
    }
}
