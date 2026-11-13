<?php

namespace App\Http\Controllers\Tests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tests\TestsIndexRequest;
use Illuminate\Contracts\View\View;

class TestsController extends Controller
{
    public function __invoke(TestsIndexRequest $request): View
    {
        return view('tests.index');
    }
}
