<?php

namespace App\Http\Requests\Customers;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CustomerIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }
}
