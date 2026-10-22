<?php

namespace App\Actions\Works;

use App\Dtos\Works\WorkDto;
use App\Models\Work;

class UpdateWork
{
    public function handle(Work $work, WorkDto $data): Work
    {
        $work->update($data->toArray());

        return $work;
    }
}
