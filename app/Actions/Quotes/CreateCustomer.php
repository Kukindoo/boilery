<?php

namespace App\Actions\Quotes;

use App\Models\User;
use Illuminate\Support\Str;

class CreateCustomer
{
    public function handle($form): User
    {
        return User::firstOrCreate(
            [
                'email' => $form->email,
            ],
            [
                'name' => $form->firstName . ' ' . $form->lastName,
                'password' => bcrypt(Str::password()),
            ]);
    }
}
