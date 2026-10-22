<?php

namespace App\Actions\Works;

use App\Dtos\Works\WorkDto;
use App\Models\Work;

class CreateWork
{
    public function handle(WorkDto $data): Work
    {
        return Work::create($data->toArray());
    }
}
