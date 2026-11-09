<?php

namespace App\Livewire\AssemblyActions;

use App\Livewire\Forms\AssemblyAction\AssemblyActionForm;
use App\Models\AssemblyAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;

class AssemblyActionEdit extends Component
{
    public AssemblyActionForm $form;

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function mount(AssemblyAction $assemblyAction): void
    {
        Gate::authorize('update', $assemblyAction);

        $this->form->initForm($assemblyAction);
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        Gate::authorize('update', $this->form->assemblyAction);

        $assemblyAction = $this->form->update();
        $this->redirect(route('assembly-actions.show', $assemblyAction));
    }

    public function render(): View
    {
        return view('livewire.assembly-actions.assembly-action-edit');
    }
}
