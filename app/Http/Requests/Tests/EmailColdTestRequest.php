<?php

namespace App\Http\Requests\Tests;

use Illuminate\Foundation\Http\FormRequest;

class EmailColdTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasAnyRole(\App\Enums\Roles::ADMIN->value, \App\Enums\Roles::STAKEHOLDER->value);
    }
}
