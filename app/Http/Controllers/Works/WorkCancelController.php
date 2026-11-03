<?php

namespace App\Http\Controllers\Works;

use App\Actions\Works\CancelWork;
use App\Http\Controllers\Controller;
use App\Models\Work;

class WorkCancelController extends Controller
{
    public function __invoke(CancelWork $cancelWork, Work $work)
    {
        $cancelWork->handle($work);

        return redirect()->route('works.show', $work);
    }
}
