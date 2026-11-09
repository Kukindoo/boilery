<?php

namespace App\Livewire\AssemblyActions;

use App\Livewire\Forms\AssemblyAction\AssemblyActionForm;
use App\Models\AssemblyAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class AssemblyActionCreate extends Component
{
    public AssemblyActionForm $form;

    /**
     * @throws AuthorizationException
     */
    public function mount(): void
    {
        Gate::authorize('create', AssemblyAction::class);
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        Gate::authorize('create', AssemblyAction::class);

        $assemblyAction = $this->form->submit();
        $this->redirect(route('assembly-actions.show', $assemblyAction));
    }

    public function render(): View
    {
        return view('livewire.assembly-actions.assembly-action-create');
    }
}
