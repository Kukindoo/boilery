<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UserIndexRequest;
use Illuminate\Contracts\View\View;

class UsersController extends Controller
{
    public function index(UserIndexRequest $request): View
    {
        return view('users.index');
    }
}
