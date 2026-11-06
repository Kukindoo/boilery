<?php

namespace App\Actions\Users;

use App\Dtos\NamePlate;
use App\Enums\Roles;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCustomer
{
    public const string EMAIL_ALREADY_EXISTS_MESSAGE = 'Tato e-mailová adresa je již evidována. Přihlašte se prosím nebo nás kontaktujte telefonicky.';

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

        if (! $user->wasRecentlyCreated && $user->id !== auth()->id()) {
            throw ValidationException::withMessages([
                'email' => self::EMAIL_ALREADY_EXISTS_MESSAGE,
            ]);
        }

        $user->assignRole(Roles::CUSTOMER->value);

        $user->update([
            'phone' => $namePlate->phone,
            'address' => $namePlate->address ?: null,
        ]);

        return $user;
    }
}
