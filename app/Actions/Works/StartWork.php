<?php

namespace App\Actions\Works;

use App\Models\Work;
use Illuminate\Support\Facades\Gate;

class StartWork
{
    public function handle(Work $work): void
    {
        Gate::authorize('start', $work);
        $work->start();

        activity('works')
            ->performedOn($work)
            ->log('Work started');
    }
}
