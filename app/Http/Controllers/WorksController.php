<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\WorkCreateRequest;
use App\Http\Requests\Works\WorkIndexRequest;
use Illuminate\Contracts\View\View;

class WorksController extends Controller
{
    public function index(WorkIndexRequest $request): View
    {
        return view('works.index');
    }

    public function show($work)
    {
        return view('works.show', compact('work'));
    }

    public function create(WorkCreateRequest $request): View
    {
        return view('works.create');
    }

    public function edit($work)
    {
        return view('works.edit', compact('work'));
    }
}
