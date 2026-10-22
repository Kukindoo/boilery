<?php

namespace App\Actions\Works;

use App\Models\Work;
use Illuminate\Support\Facades\Gate;

class ForceDeleteWork
{
    public function handle(Work $work): void
    {
        Gate::authorize('forceDelete', $work);
        $work->forceDelete();
    }
}
