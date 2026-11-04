<?php

namespace App\Actions\Works;

use App\Models\Work;
use Illuminate\Support\Facades\Gate;

class EndWork
{
    public function handle(Work $work): void
    {
        Gate::authorize('end', $work);
        $work->end();

        activity('works')
            ->performedOn($work)
            ->log('Work finished');
    }
}
