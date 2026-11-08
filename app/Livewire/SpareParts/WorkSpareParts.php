<?php

namespace App\Livewire\SpareParts;

use App\Actions\SpareParts\DetachSparePartFromWork;
use App\Models\SparePart;
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
use Throwable;

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
        Gate::authorize('viewAny', SparePart::class);
    }

    /**
     * @throws Throwable
     */
    public function remove(int $id): void
    {
        Gate::authorize('manageSpareParts', $this->work);
        app(DetachSparePartFromWork::class)->handle($this->work, $this->work->spareParts()->findOrFail($id));
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
        Gate::authorize('viewAny', SparePart::class);

        $parts = $this->work->spareParts()->orderBy('name')->get();
        $linePrices = [];
        $totalPrice = Price::fromCents(0);
        foreach ($parts as $part) {
            $linePrices[$part->id] = Price::fromCents($part->price_in_cents)
                ->multipliedBy((int) $part->pivot->getAttribute('amount'));
            $totalPrice = $totalPrice->plus($linePrices[$part->id]);
        }

        return view('livewire.spare-parts.work-spare-parts', compact('parts', 'linePrices', 'totalPrice'));
    }
}
