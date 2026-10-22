<?php

namespace App\Actions\Works;

use App\Models\Work;
use Illuminate\Support\Facades\Gate;

class DeleteWork
{
    public function handle(Work $work): void
    {
        Gate::authorize('delete', $work);
        $work->delete();
    }
}
