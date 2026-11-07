<?php

namespace App\Actions\Users;

use App\Dtos\NamePlate;
use App\Enums\Roles;
use App\Models\User;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateCustomer
{
    use LogsActionActivity;

    public const string EMAIL_ALREADY_EXISTS_MESSAGE = 'Tato e-mailová adresa je již evidována. Přihlašte se prosím nebo nás kontaktujte telefonicky.';

    /**
     * @throws Throwable
     */
    public function handle(NamePlate $namePlate): User
    {
        try {
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

            $this->logActionCompleted('users', $user, $user->wasRecentlyCreated ? 'Customer created' : 'Customer updated');

            return $user;
        } catch (Throwable $exception) {
            $this->logActionAttempt('users', null, $exception);

            throw $exception;
        }
    }
}
