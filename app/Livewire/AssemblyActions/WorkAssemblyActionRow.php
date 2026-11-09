<?php

namespace App\Livewire\AssemblyActions;

use App\Actions\AssemblyActions\DetachAssemblyActionFromWork;
use App\Livewire\Forms\AssemblyAction\WorkAssemblyActionPriceForm;
use App\Models\AssemblyAction;
use App\Models\Work;
use App\ValueObjects\Price;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class WorkAssemblyActionRow extends Component
{
    public WorkAssemblyActionPriceForm $form;

    public bool $editing = false;

    public bool $confirmingDeletion = false;

    #[Locked]
    public int $priceInCents;

    #[Locked]
    public Work $work;

    #[Locked]
    public AssemblyAction $action;

    public function mount(Work $work, AssemblyAction $action, int $priceInCents = 0): void
    {
        Gate::authorize('view', $work);

        $this->work = $work;
        $this->action = $action;
        $this->priceInCents = $priceInCents;
        $this->form->initForm($work, $action, $priceInCents);
    }

    public function edit(): void
    {
        Gate::authorize('manageAssemblyActions', $this->work);

        $action = $this->work->assemblyActions()->findOrFail($this->action->id);
        $this->priceInCents = (int) $action->pivot->getAttribute('price_in_cents');
        $this->form->initForm($this->work, $action, $this->priceInCents);
        $this->form->resetValidation();
        $this->editing = true;
    }

    public function cancel(): void
    {
        Gate::authorize('manageAssemblyActions', $this->work);

        $this->form->price = Price::fromCents($this->priceInCents)->forInput();
        $this->form->resetValidation();
        $this->editing = false;
    }

    /** @throws Throwable */
    public function submit(): void
    {
        $this->form->submit();
        $this->priceInCents = Price::fromInput($this->form->price)->inCents();
        $this->editing = false;
        $this->dispatch('assembly-actions-changed');
    }

    public function confirmDelete(): void
    {
        Gate::authorize('manageAssemblyActions', $this->work);

        $this->work->assemblyActions()->findOrFail($this->action->id);
        $this->confirmingDeletion = true;
    }

    /** @throws Throwable */
    public function delete(): void
    {
        Gate::authorize('manageAssemblyActions', $this->work);

        app(DetachAssemblyActionFromWork::class)->handle($this->work, $this->action);
        $this->confirmingDeletion = false;
        $this->dispatch('assembly-actions-changed');
        $this->skipRender();
    }

    public function render(): View
    {
        Gate::authorize('view', $this->work);

        return view('livewire.assembly-actions.work-assembly-action-row', [
            'price' => Price::fromCents($this->priceInCents),
        ]);
    }
}
