<?php

namespace App\Http\Controllers\Works;

use App\Actions\Works\StartWork;
use App\Http\Controllers\Controller;
use App\Models\Work;

class WorkStartController extends Controller
{
    public function __invoke(StartWork $startWork, Work $work)
    {
        $startWork->handle($work);

        return redirect()->route('works.show', $work);
    }
}
