<?php

namespace App\Http\Controllers\Works;

use App\Actions\Works\EndWork;
use App\Http\Controllers\Controller;
use App\Models\Work;

class WorkEndController extends Controller
{
    public function __invoke(EndWork $endWork, Work $work)
    {
        $endWork->handle($work);

        return redirect()->route('works.show', $work);
    }
}
