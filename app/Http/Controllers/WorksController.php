<?php

namespace App\Http\Controllers;

use App\Http\Requests\Works\WorkCreateRequest;
use App\Http\Requests\Works\WorkEditRequest;
use App\Http\Requests\Works\WorkIndexRequest;
use App\Http\Requests\Works\WorkShowRequest;
use App\Models\Work;
use Illuminate\Contracts\View\View;

class WorksController extends Controller
{
    public function index(WorkIndexRequest $request): View
    {
        return view('works.index');
    }

    public function show(WorkShowRequest $request, Work $work): View
    {
        $work->load(['user', 'quote.files']);

        return view('works.show', compact('work'));
    }

    public function create(WorkCreateRequest $request): View
    {
        return view('works.create');
    }

    public function edit(WorkEditRequest $request, Work $work): View
    {
        return view('works.edit', compact('work'));
    }
}
