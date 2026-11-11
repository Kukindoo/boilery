<?php

namespace App\Http\Controllers\Issues;

use App\Http\Controllers\Controller;
use App\Http\Requests\Issues\IssueCreateRequest;
use App\Http\Requests\Issues\IssueEditRequest;
use App\Http\Requests\Issues\IssueIndexRequest;
use App\Http\Requests\Issues\IssueShowRequest;
use App\Models\Issue;
use Illuminate\Contracts\View\View;

class IssueController extends Controller
{
    public function index(IssueIndexRequest $request): View
    {
        return view('issues.index');
    }

    public function create(IssueCreateRequest $request): View
    {
        return view('issues.create', ['customerId' => $request->filled('customer_id') ? $request->integer('customer_id') : null]);
    }

    public function show(IssueShowRequest $request, Issue $issue): View
    {
        $issue->load(['user', 'quotes', 'works']);

        return view('issues.show', ['issue' => $issue]);
    }

    public function edit(IssueEditRequest $request, Issue $issue): View
    {
        return view('issues.edit', ['issue' => $issue]);
    }
}
