<?php

namespace App\Livewire\SpareParts;

use App\Livewire\Forms\SparePart\SparePartForm;
use App\Models\SparePart;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;

class SparePartEdit extends Component
{
    public SparePartForm $form;

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function mount(SparePart $sparePart): void
    {
        Gate::authorize('update', $sparePart);
        $this->form->initForm($sparePart);
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        Gate::authorize('update', $this->form->sparePart);

        $sparePart = $this->form->update();
        $this->redirect(route('spare-parts.show', $sparePart));
    }

    public function render(): View
    {
        return view('livewire.spare-parts.spare-part-edit');
    }
}
