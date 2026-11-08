<?php

namespace App\Dtos\SpareParts;

class SparePartDto
{
    public function __construct(
        public string $name,
        public ?string $partNumber,
        public ?string $description,
        public int $priceInCents,
    ) {}

    /** @return array{name: string, part_number: ?string, description: ?string, price_in_cents: int} */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'part_number' => $this->partNumber,
            'description' => $this->description,
            'price_in_cents' => $this->priceInCents,
        ];
    }
}
