<?php

namespace App\Livewire\Forms\Customers;

use App\Actions\Customers\UpdateCustomer;
use App\Dtos\Customers\CustomerDto;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class CustomerForm extends Form
{
    #[Locked]
    public ?User $customer = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public ?string $address = null;

    public function initForm(User $customer): void
    {
        $this->customer = $customer;
        $this->name = $customer->name;
        $this->email = $customer->email;
        $this->phone = $customer->phone;
        $this->address = $customer->address;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->customer)],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function update(): User
    {
        abort_if($this->customer === null, 404);
        $customer = User::query()->findOrFail($this->customer->id);
        Gate::authorize('update', $customer);
        $this->validate();

        return app(UpdateCustomer::class)->handle($customer, new CustomerDto(
            name: $this->name,
            email: $this->email,
            phone: $this->phone,
            address: $this->address === '' ? null : $this->address,
        ));
    }
}
