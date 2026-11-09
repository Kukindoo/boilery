<?php

namespace App\Livewire\Forms\SparePart;

use App\Actions\SpareParts\UpdateWorkSparePartAmount;
use App\Models\SparePart;
use App\Models\Work;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Throwable;

class WorkSparePartAmountForm extends Form
{
    #[Locked]
    public Work $work;

    #[Locked]
    public SparePart $sparePart;

    public string $amount = '1';

    public function initForm(Work $work, SparePart $sparePart, int $amount): void
    {
        $this->work = $work;
        $this->sparePart = $sparePart;
        $this->amount = (string) $amount;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        Gate::authorize('manageSpareParts', $this->work);
        $this->validate();
        app(UpdateWorkSparePartAmount::class)->handle($this->work, $this->sparePart, (int) $this->amount);
    }
}
