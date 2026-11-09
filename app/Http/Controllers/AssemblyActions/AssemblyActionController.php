<?php

namespace App\Http\Controllers\AssemblyActions;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssemblyActions\AssemblyActionCreateRequest;
use App\Http\Requests\AssemblyActions\AssemblyActionEditRequest;
use App\Http\Requests\AssemblyActions\AssemblyActionIndexRequest;
use App\Http\Requests\AssemblyActions\AssemblyActionShowRequest;
use App\Models\AssemblyAction;
use Illuminate\Contracts\View\View;

class AssemblyActionController extends Controller
{
    public function index(AssemblyActionIndexRequest $request): View
    {
        return view('assembly-actions.index');
    }

    public function create(AssemblyActionCreateRequest $request): View
    {
        return view('assembly-actions.create');
    }

    public function show(AssemblyActionShowRequest $request, AssemblyAction $assemblyAction): View
    {
        return view('assembly-actions.show', compact('assemblyAction'));
    }

    public function edit(AssemblyActionEditRequest $request, AssemblyAction $assemblyAction): View
    {
        return view('assembly-actions.edit', compact('assemblyAction'));
    }
}
