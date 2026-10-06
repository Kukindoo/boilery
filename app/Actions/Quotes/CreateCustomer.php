<?php

namespace App\Actions\Quotes;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Support\Str;

class CreateCustomer
{
    public function handle($form): User
    {
        $user = User::firstOrCreate(
            [
                'email' => $form->email,
            ],
            [
                'name' => $form->firstName . ' ' . $form->lastName,
                'password' => bcrypt(Str::password()),
            ]);

        $user->assignRole(Roles::CUSTOMER->value);

        return $user;
    }
}
