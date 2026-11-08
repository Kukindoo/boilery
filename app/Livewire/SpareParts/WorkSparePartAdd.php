<?php

namespace App\Livewire\SpareParts;

use App\Livewire\Forms\SparePart\WorkSparePartForm;
use App\Models\SparePart;
use App\Models\Work;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class WorkSparePartAdd extends Component
{
    public WorkSparePartForm $form;

    /**
     * @throws AuthorizationException
     */
    public function mount(Work $work): void
    {
        Gate::authorize('manageSpareParts', $work);
        $this->form->initForm($work);
    }

    /**
     * @throws AuthorizationException
     */
    public function open(): void
    {
        Gate::authorize('manageSpareParts', $this->form->work);
        $this->form->reset('sparePartId', 'amount');
        $this->form->resetValidation();
        $this->modal('add-work-spare-part')->show();
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        $this->form->submit();
        $this->form->reset('sparePartId', 'amount');
        $this->modal('add-work-spare-part')->close();
        $this->dispatch('spare-part-added');
    }

    /**
     * @throws AuthorizationException
     * @throws QueryException
     */
    public function render(): View
    {
        Gate::authorize('manageSpareParts', $this->form->work);

        return view('livewire.spare-parts.work-spare-part-add', [
            'availableParts' => SparePart::query()
                ->whereDoesntHave('works', fn ($query) => $query->whereKey($this->form->work->id))
                ->orderBy('name')->get(),
        ]);
    }
}
