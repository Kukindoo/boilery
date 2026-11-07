<?php

namespace App\Dtos\Customers;

class CustomerDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public ?string $address,
    ) {}

    /** @return array{name: string, email: string, phone: string, address: ?string} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ];
    }
}
