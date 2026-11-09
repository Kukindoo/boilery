<?php

namespace App\Dtos\Works;

class ServiceProtocolCustomerDto
{
    public function __construct(
        public ?string $name,
        public ?string $address,
        public ?string $phone,
    ) {}
}
