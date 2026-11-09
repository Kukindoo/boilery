<?php

namespace App\Livewire\Forms\AssemblyAction;

use App\Actions\AssemblyActions\UpdateWorkAssemblyActionPrice;
use App\Models\AssemblyAction;
use App\Models\Work;
use App\ValueObjects\Price;
use Closure;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Throwable;

class WorkAssemblyActionPriceForm extends Form
{
    #[Locked]
    public Work $work;

    #[Locked]
    public AssemblyAction $assemblyAction;

    public string $price = '';

    public function initForm(Work $work, AssemblyAction $assemblyAction, int $priceInCents): void
    {
        $this->work = $work;
        $this->assemblyAction = $assemblyAction;
        $this->price = Price::fromCents($priceInCents)->forInput();
    }

    public function rules(): array
    {
        return [
            'price' => [
                'required',
                'string',
                'regex:' . config('price.input_regex'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    try {
                        Price::fromInput($value);
                    } catch (InvalidArgumentException $exception) {
                        $fail($exception->getMessage());
                    }
                },
            ],
        ];
    }

    /** @throws Throwable */
    public function submit(): void
    {
        Gate::authorize('manageAssemblyActions', $this->work);

        $this->validate();
        app(UpdateWorkAssemblyActionPrice::class)->handle($this->work, $this->assemblyAction, Price::fromInput($this->price));
    }
}
