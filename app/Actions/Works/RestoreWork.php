<?php

namespace App\Actions\Works;

use App\Models\Work;
use Illuminate\Support\Facades\Gate;

class RestoreWork
{
    public function handle(Work $work): void
    {
        Gate::authorize('restore', $work);
        $work->restore();
    }
}
