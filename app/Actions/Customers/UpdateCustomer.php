<?php

namespace App\Actions\Customers;

use App\Dtos\Customers\CustomerDto;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateCustomer
{
    public function handle(User $customer, CustomerDto $data): User
    {
        Gate::authorize('update', $customer);

        $customer->fill($data->toArray());

        if ($customer->isDirty('email')) {
            $customer->email_verified_at = null;
        }

        $customer->save();

        return $customer;
    }
}
