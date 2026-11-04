<?php

namespace App\Actions\Works;

use App\Models\Work;
use Illuminate\Support\Facades\Gate;

class CancelWork
{
    public function handle(Work $work): void
    {
        Gate::authorize('cancel', $work);
        $work->cancel();

        activity('works')
            ->performedOn($work)
            ->log('Work cancelled');
    }
}
