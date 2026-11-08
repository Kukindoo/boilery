<?php

namespace App\Livewire\SpareParts;

use App\Livewire\Forms\SparePart\SparePartForm;
use App\Models\SparePart;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

class SparePartCreate extends Component
{
    public SparePartForm $form;

    /**
     * @throws AuthorizationException
     */
    public function mount(): void
    {
        Gate::authorize('create', SparePart::class);
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        Gate::authorize('create', SparePart::class);

        $sparePart = $this->form->submit();
        $this->redirect(route('spare-parts.show', $sparePart));
    }

    public function render(): View
    {
        return view('livewire.spare-parts.spare-part-create');
    }
}
