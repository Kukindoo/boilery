<?php

namespace App\Livewire\Forms\SparePart;

use App\Actions\SpareParts\CreateSparePart;
use App\Actions\SpareParts\UpdateSparePart;
use App\Dtos\SpareParts\SparePartDto;
use App\Models\SparePart;
use App\ValueObjects\Price;
use Closure;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Throwable;

class SparePartForm extends Form
{
    #[Locked]
    public ?SparePart $sparePart = null;

    public string $name = '';

    public ?string $partNumber = null;

    public ?string $description = null;

    public string $price = '0,00';

    /**
     * @throws InvalidArgumentException
     */
    public function initForm(SparePart $sparePart): void
    {
        $this->sparePart = $sparePart;
        $this->name = $sparePart->name;
        $this->partNumber = $sparePart->part_number;
        $this->description = $sparePart->description;
        $this->price = Price::fromCents($sparePart->price_in_cents)->forInput();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'partNumber' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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

    /**
     * @throws Throwable
     */
    public function submit(): SparePart
    {
        Gate::authorize('create', SparePart::class);
        $this->validate();

        return app(CreateSparePart::class)->handle($this->toDto());
    }

    /**
     * @throws Throwable
     */
    public function update(): SparePart
    {
        abort_if($this->sparePart === null, 404);
        Gate::authorize('update', $this->sparePart);
        $this->validate();

        return app(UpdateSparePart::class)->handle($this->sparePart, $this->toDto());
    }

    /**
     * @throws InvalidArgumentException
     */
    private function toDto(): SparePartDto
    {
        return new SparePartDto(
            name: mb_trim($this->name),
            partNumber: filled($this->partNumber) ? mb_trim($this->partNumber) : null,
            description: filled($this->description) ? mb_trim($this->description) : null,
            priceInCents: Price::fromInput($this->price)->inCents(),
        );
    }
}
