<?php

namespace App\Livewire\SpareParts;

use App\Actions\SpareParts\DetachSparePartFromWork;
use App\Livewire\Forms\SparePart\WorkSparePartAmountForm;
use App\Models\SparePart;
use App\Models\Work;
use App\Traits\HasDeleteButton;
use App\ValueObjects\Price;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use OverflowException;
use Throwable;

class WorkSparePartRow extends Component
{
    use HasDeleteButton;

    public WorkSparePartAmountForm $form;

    #[Locked]
    public Work $work;

    #[Locked]
    public SparePart $part;

    #[Locked]
    public int $amount;

    public bool $editing = false;

    /**
     * @throws AuthorizationException
     */
    public function mount(Work $work, SparePart $part, int $amount): void
    {
        Gate::authorize('view', $work);

        $this->work = $work;
        $this->part = $part;
        $this->amount = $amount;
        $this->form->initForm($work, $part, $amount);
    }

    /**
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     * @throws QueryException
     */
    public function edit(): void
    {
        Gate::authorize('manageSpareParts', $this->work);

        $part = $this->work->spareParts()->findOrFail($this->part->id);
        $this->amount = (int) $part->pivot->getAttribute('amount');
        $this->form->initForm($this->work, $part, $this->amount);
        $this->form->resetValidation();
        $this->editing = true;
    }

    /**
     * @throws AuthorizationException
     */
    public function cancel(): void
    {
        Gate::authorize('manageSpareParts', $this->work);

        $this->form->amount = (string) $this->amount;
        $this->form->resetValidation();
        $this->editing = false;
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        Gate::authorize('manageSpareParts', $this->work);

        $this->form->submit();
        $this->amount = (int) $this->form->amount;
        $this->editing = false;
        $this->dispatch('spare-parts-changed');
        if ($this->amount === 0) {
            $this->skipRender();
        }
    }

    /**
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     * @throws OverflowException
     */
    public function render(): View
    {
        Gate::authorize('view', $this->work);

        return view('livewire.spare-parts.work-spare-part-row', [
            'linePrice' => Price::fromCents($this->part->price_in_cents)->multipliedBy($this->amount),
        ]);
    }

    protected function authorizeDeletion(): void
    {
        Gate::authorize('manageSpareParts', $this->work);

        $this->work->spareParts()->findOrFail($this->part->id);
    }

    /**
     * @throws Throwable
     */
    protected function performDeletion(): void
    {
        app(DetachSparePartFromWork::class)->handle($this->work, $this->part);
        $this->dispatch('spare-parts-changed');
        $this->skipRender();
    }
}
