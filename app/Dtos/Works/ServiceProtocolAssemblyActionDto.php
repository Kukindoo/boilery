<?php

namespace App\Dtos\Works;

class ServiceProtocolAssemblyActionDto
{
    public function __construct(
        public string $code,
        public string $name,
        public string $price,
    ) {}
}
