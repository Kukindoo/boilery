<?php

namespace App\Dtos\AssemblyActions;

class AssemblyActionDto
{
    public function __construct(
        public string $name,
        public ?string $description,
        public string $code,
    ) {}

    /** @return array{code: string, name: string, description: ?string} */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
