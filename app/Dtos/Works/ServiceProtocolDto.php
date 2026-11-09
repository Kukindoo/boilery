<?php

namespace App\Dtos\Works;

use App\Models\Work;

class ServiceProtocolDto
{
    /**
     * @param  list<ServiceProtocolPartDto>  $parts
     * @param  list<ServiceProtocolAssemblyActionDto>  $assemblyActions
     */
    public function __construct(
        public Work $work,
        public ServiceProtocolCustomerDto $customer,
        public array $parts,
        public string $partsTotal,
        public array $assemblyActions,
        public string $assemblyActionsTotal,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'work' => $this->work,
            'customer' => $this->customer,
            'parts' => $this->parts,
            'partsTotal' => $this->partsTotal,
            'assemblyActions' => $this->assemblyActions,
            'assemblyActionsTotal' => $this->assemblyActionsTotal,
        ];
    }
}
