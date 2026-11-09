<?php

namespace App\Livewire\SpareParts;

use App\Models\Work;
use App\ValueObjects\Price;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use OverflowException;

class WorkSpareParts extends Component
{
    #[Locked]
    public Work $work;

    /**
     * @throws AuthorizationException
     */
    public function mount(Work $work): void
    {
        $this->work = $work;
        Gate::authorize('view', $work);
    }

    /**
     * @throws AuthorizationException
     * @throws QueryException
     * @throws InvalidArgumentException
     * @throws OverflowException
     */
    public function render(): View
    {
        Gate::authorize('view', $this->work);

        $parts = $this->work->spareParts()->orderBy('name')->get();
        $totalPrice = Price::fromCents(0);
        foreach ($parts as $part) {
            $linePrice = Price::fromCents($part->price_in_cents)
                ->multipliedBy((int) $part->pivot->getAttribute('amount'));
            $totalPrice = $totalPrice->plus($linePrice);
        }

        return view('livewire.spare-parts.work-spare-parts', compact('parts', 'totalPrice'));
    }
}
