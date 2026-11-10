<?php

namespace App\Livewire\Forms\AssemblyAction;

use App\Actions\AssemblyActions\AttachAssemblyActionToWork;
use App\Models\AssemblyAction;
use App\Models\Work;
use App\ValueObjects\Price;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Throwable;

class WorkAssemblyActionForm extends Form
{
    #[Locked]
    public Work $work;

    public ?string $assemblyActionId = null;

    public string $price = '';

    public function initForm(Work $work): void
    {
        $this->work = $work;
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
            'assemblyActionId' => ['required', 'integer', Rule::exists('assembly_actions', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @throws Throwable
     */
    public function submit(): void
    {
        Gate::authorize('manageAssemblyActions', $this->work);

        Gate::authorize('viewAny', AssemblyAction::class);

        $this->validate();
        try {
            app(AttachAssemblyActionToWork::class)->handle($this->work, AssemblyAction::findOrFail($this->assemblyActionId), Price::fromInput($this->price));
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                Arr::prependKeysWith($exception->errors(), $this->getPropertyName() . '.'),
            );
        }
    }
}
