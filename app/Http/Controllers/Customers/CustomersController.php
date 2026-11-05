<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomerEditRequest;
use App\Http\Requests\Customers\CustomerIndexRequest;
use App\Http\Requests\Customers\CustomerShowRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;

class CustomersController extends Controller
{
    public function index(CustomerIndexRequest $request): View
    {
        return view('customers.index');
    }

    public function show(CustomerShowRequest $request, User $customer): View
    {
        return view('customers.show', compact('customer'));
    }

    public function edit(CustomerEditRequest $request, User $customer): View
    {
        return view('customers.edit', compact('customer'));
    }
}
