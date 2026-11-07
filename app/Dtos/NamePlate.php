<?php

namespace App\Dtos;

class NamePlate
{
    public string $firstName;

    public string $lastName;

    public string $email;

    public string $phone;

    public ?string $address;

    public function __construct(string $firstName, string $lastName, string $email, string $phone, ?string $address = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phone = $phone;
        $this->address = $address;
    }
}
