<?php

namespace App\Livewire\AssemblyActions;

use App\Models\Work;
use App\ValueObjects\Price;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class WorkAssemblyActions extends Component
{
    #[Locked]
    public Work $work;

    public function mount(Work $work): void
    {
        Gate::authorize('view', $work);

        $this->work = $work;
    }

    public function render(): View
    {
        Gate::authorize('view', $this->work);

        $actions = $this->work->assemblyActions()->orderBy('name')->get();
        $totalPrice = Price::fromCents(0);
        foreach ($actions as $action) {
            $totalPrice = $totalPrice->plus(Price::fromCents((int) $action->pivot->getAttribute('price_in_cents')));
        }

        return view('livewire.assembly-actions.work-assembly-actions', compact('actions', 'totalPrice'));
    }
}
