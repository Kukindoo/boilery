<?php

namespace App\Actions\Customers;

use App\Dtos\Customers\CustomerDto;
use App\Models\User;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UpdateCustomer
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(User $customer, CustomerDto $data): User
    {
        try {
            Gate::authorize('update', $customer);

            $customer->fill($data->toArray());

            if ($customer->isDirty('email')) {
                $customer->email_verified_at = null;
            }

            $customer->save();

            $this->logActionCompleted('users', $customer, 'Customer updated');

            return $customer;
        } catch (Throwable $exception) {
            $this->logActionAttempt('users', $customer, $exception);

            throw $exception;
        }
    }
}
