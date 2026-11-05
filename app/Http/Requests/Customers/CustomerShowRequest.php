<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

class CustomerShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('customer'));
    }
}
