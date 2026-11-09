<?php

namespace App\Livewire\Forms\SparePart;

use App\Actions\SpareParts\AttachSparePartToWork;
use App\Models\SparePart;
use App\Models\Work;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Throwable;

class WorkSparePartForm extends Form
{
    #[Locked]
    public Work $work;

    public ?string $sparePartId = null;

    public string $amount = '1';

    public function initForm(Work $work): void
    {
        $this->work = $work;
    }

    public function rules(): array
    {
        return [
            'sparePartId' => ['required', 'integer', Rule::exists('spare_parts', 'id')->whereNull('deleted_at')],
            'amount' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        Gate::authorize('manageSpareParts', $this->work);
        $this->validate();
        app(AttachSparePartToWork::class)->handle($this->work, SparePart::findOrFail($this->sparePartId), (int) $this->amount);
    }
}
