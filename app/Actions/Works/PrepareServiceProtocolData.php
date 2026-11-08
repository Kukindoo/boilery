<?php

namespace App\Actions\Works;

use App\Models\Work;

class PrepareServiceProtocolData
{
    /** @return array{work: Work, customer: array{name: ?string, address: ?string, phone: ?string}} */
    public function handle(Work $work): array
    {
        $work->loadMissing('user');

        return [
            'work' => $work,
            'customer' => [
                'name' => $work->user?->name,
                'address' => $work->user?->address,
                'phone' => $work->user?->phone,
            ],
        ];
    }
}
