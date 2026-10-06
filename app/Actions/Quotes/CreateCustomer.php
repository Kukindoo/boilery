<?php

namespace App\Actions\Quotes;

use App\Dtos\NamePlate;
use App\Enums\Roles;
use App\Models\User;
use Illuminate\Support\Str;

class CreateCustomer
{
    public function handle(NamePlate $namePlate): User
    {
        $user = User::firstOrCreate(
            [
                'email' => $namePlate->email,
            ],
            [
                'name' => $namePlate->firstName . ' ' . $namePlate->lastName,
                'password' => bcrypt(Str::password()),
            ]);

        $user->assignRole(Roles::CUSTOMER->value);

        return $user;
    }
}
