<?php

namespace App\Livewire\Customers;

use App\Livewire\Forms\Customers\CustomerForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CustomerEdit extends Component
{
    public CustomerForm $form;

    public function mount(User $customer): void
    {
        Gate::authorize('update', $customer);
        $this->form->initForm($customer);
    }

    public function submit(): void
    {
        $customer = $this->form->update();
        $this->redirect(route('customers.show', $customer));
    }

    public function render(): View
    {
        return view('livewire.customers.customer-edit');
    }
}
