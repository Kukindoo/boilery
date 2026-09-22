<?php

namespace App\Actions\Quotes;

use App\Models\RequestedQuotes;
use App\Models\User;
use Illuminate\Support\Str;

class CreateCustomer
{
    public function handle(RequestedQuotes $quote): User
    {
        return User::firstOrCreate(
            [
                'email' => $quote->email,
            ],
            [
                'name' => $quote->first_name . ' ' . $quote->last_name,
                'password' => bcrypt(Str::password()),
            ]);
    }
}
