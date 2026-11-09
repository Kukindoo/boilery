<?php

namespace App\Http\Controllers\Works;

use App\Actions\Works\PrepareServiceProtocolData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Works\WorkCreateRequest;
use App\Http\Requests\Works\WorkEditRequest;
use App\Http\Requests\Works\WorkIndexRequest;
use App\Http\Requests\Works\WorkShowRequest;
use App\Models\Work;
use Illuminate\Contracts\View\View;
use Throwable;

class WorksController extends Controller
{
    public function index(WorkIndexRequest $request): View
    {
        return view('works.index');
    }

    /**
     * @throws Throwable
     */
    public function show(WorkShowRequest $request, PrepareServiceProtocolData $prepareServiceProtocolData, Work $work): View
    {
        $work->load(['user', 'files']);

        $pdfContent = view('pdfs.service-protocol-pdf', $prepareServiceProtocolData->handle($work)->toArray())->render();

        return view('works.show', compact('work', 'pdfContent'));
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
