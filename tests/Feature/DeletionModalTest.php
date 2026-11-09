<?php

namespace Tests\Feature;

use App\Livewire\Modals\Deletion;
use Livewire\Livewire;
use Tests\TestCase;

class DeletionModalTest extends TestCase
{
    public function test_confirmation_closes_the_modal_and_notifies_its_parent(): void
    {
        Livewire::test(Deletion::class, [
            'heading' => 'Odebrat pracovní úkon?',
            'text' => 'Opravdu chcete odebrat tento pracovní úkon ze zakázky?',
            'confirmLabel' => 'Odebrat',
        ])
            ->assertSee('Odebrat pracovní úkon?')->assertSee('Odebrat')
            ->set('show', true)->call('delete')
            ->assertSet('show', false)->assertDispatched('confirmed');
    }

    public function test_cancelling_closes_the_modal_without_confirming(): void
    {
        Livewire::test(Deletion::class, ['heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.'])
            ->assertSee('data-flux-modal-close', false)
            ->set('show', true)->set('show', false)
            ->assertSet('show', false)->assertNotDispatched('confirmed');
    }

    public function test_a_closed_modal_does_not_dispatch_confirmation(): void
    {
        Livewire::test(Deletion::class, ['heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.'])
            ->call('delete')->assertNotDispatched('confirmed');
    }

    public function test_modal_instances_keep_independent_state_and_escape_their_content(): void
    {
        $first = Livewire::test(Deletion::class, [
            'heading' => '<script>unsafe heading</script>', 'text' => '<script>unsafe text</script>',
        ])->set('show', true);
        $second = Livewire::test(Deletion::class, ['heading' => 'Other modal', 'text' => 'Other item'])
            ->assertSet('show', false);

        $first->assertSee('<script>unsafe heading</script>')
            ->assertDontSee('<script>unsafe heading</script>', false)
            ->assertSee('<script>unsafe text</script>')
            ->assertDontSee('<script>unsafe text</script>', false)
            ->call('delete')->assertDispatched('confirmed');
        $second->assertSet('show', false)->assertNotDispatched('confirmed');
    }

    public function test_optional_native_trigger_targets_its_own_modal(): void
    {
        $modal = Livewire::test(Deletion::class, [
            'heading' => 'Odebrat pracovní úkon?',
            'text' => 'Odebrat úkon ze zakázky?',
            'triggerLabel' => 'Odebrat úkon',
            'confirmLabel' => 'Odebrat',
        ])->assertSee('Odebrat úkon')->assertSee('data-flux-modal-trigger', false)
            ->assertDontSee('wire:click="open"', false)->assertSet('show', false);
        $modalName = 'deletion-' . $modal->instance()->getId();
        $modal->assertSee("name: '" . $modalName . "'", false)
            ->assertSee('data-modal="' . $modalName . '"', false)
            ->assertNotDispatched('confirmed');
    }

    public function test_trigger_can_be_omitted_for_parent_controlled_modals(): void
    {
        Livewire::test(Deletion::class, ['heading' => 'Smazat?', 'text' => 'Potvrďte odstranění.'])
            ->assertDontSee('data-flux-modal-trigger', false)->assertSet('show', false);
    }
}
