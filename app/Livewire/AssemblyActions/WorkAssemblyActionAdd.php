<?php

namespace App\Livewire\AssemblyActions;

use App\Livewire\Forms\AssemblyAction\WorkAssemblyActionForm;
use App\Models\AssemblyAction;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class WorkAssemblyActionAdd extends Component
{
    public WorkAssemblyActionForm $form;

    /**
     * @throws AuthorizationException
     */
    public function mount(Work $work): void
    {
        Gate::authorize('manageAssemblyActions', $work);

        Gate::authorize('viewAny', AssemblyAction::class);

        $this->form->initForm($work);
    }

    /**
     * @throws AuthorizationException
     */
    public function open(): void
    {
        Gate::authorize('manageAssemblyActions', $this->form->work);

        Gate::authorize('viewAny', AssemblyAction::class);

        $this->form->reset('assemblyActionId', 'price');
        $this->form->resetValidation();
        $this->modal('add-work-assembly-action')->show();
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        $this->form->submit();
        $this->form->reset('assemblyActionId', 'price');
        $this->modal('add-work-assembly-action')->close();
        $this->dispatch('assembly-action-added');
    }

    /**
     * @throws AuthorizationException
     * @throws QueryException
     */
    public function render(): View
    {
        Gate::authorize('manageAssemblyActions', $this->form->work);

        Gate::authorize('viewAny', AssemblyAction::class);

        return view('livewire.assembly-actions.work-assembly-action-add', [
            'availableActions' => AssemblyAction::query()
                ->whereDoesntHave('works', fn ($query) => $query->whereKey($this->form->work->id))
                ->orderBy('name')->get(),
        ]);
    }
}
