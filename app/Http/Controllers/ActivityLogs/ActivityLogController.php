<?php

namespace App\Http\Controllers\ActivityLogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityLogs\ActivityLogIndexRequest;
use Illuminate\Contracts\View\View;

class ActivityLogController extends Controller
{
    public function index(ActivityLogIndexRequest $request): View
    {
        return view('activity-logs.index');
    }
}
