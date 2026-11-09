<?php

namespace App\Http\Controllers\SpareParts;

use App\Http\Controllers\Controller;
use App\Http\Requests\SpareParts\SparePartCreateRequest;
use App\Http\Requests\SpareParts\SparePartEditRequest;
use App\Http\Requests\SpareParts\SparePartIndexRequest;
use App\Http\Requests\SpareParts\SparePartShowRequest;
use App\Models\SparePart;
use Illuminate\Contracts\View\View;

class SparePartController extends Controller
{
    public function index(SparePartIndexRequest $request): View
    {
        return view('spare-parts.index');
    }

    public function create(SparePartCreateRequest $request): View
    {
        return view('spare-parts.create');
    }

    public function show(SparePartShowRequest $request, SparePart $sparePart): View
    {
        return view('spare-parts.show', compact('sparePart'));
    }

    public function edit(SparePartEditRequest $request, SparePart $sparePart): View
    {
        return view('spare-parts.edit', compact('sparePart'));
    }
}
