<?php

namespace App\Actions\Works;

use App\Dtos\Works\WorkDto;
use App\Models\Work;
use Illuminate\Support\Facades\Gate;

class UpdateWork
{
    public function handle(Work $work, WorkDto $data): Work
    {
        Gate::authorize('update', $work);

        $work->update($data->toArray());

        return $work;
    }
}
