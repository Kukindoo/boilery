<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\RoleIndexRequest;
use App\Http\Requests\Roles\RoleShowRequest;
use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(RoleIndexRequest $request): View
    {
        return view('roles.index');
    }

    public function show(RoleShowRequest $request, Role $role): View
    {
        return view('roles.show', compact('role'));
    }
}
