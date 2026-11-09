<?php

namespace App\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permissions\PermissionIndexRequest;
use App\Http\Requests\Permissions\PermissionShowRequest;
use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(PermissionIndexRequest $request): View
    {
        return view('permissions.index');
    }

    public function show(PermissionShowRequest $request, Permission $permission): View
    {
        return view('permissions.show', compact('permission'));
    }
}
