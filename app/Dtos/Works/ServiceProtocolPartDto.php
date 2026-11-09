<?php

namespace App\Dtos\Works;

class ServiceProtocolPartDto
{
    public function __construct(
        public ?string $number,
        public string $name,
        public int $quantity,
        public string $price,
    ) {}
}
