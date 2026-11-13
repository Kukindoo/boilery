<?php

namespace App\Http\Requests\Tests;

use Illuminate\Foundation\Http\FormRequest;

class EmailColdTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(\App\Enums\Roles::ADMIN->value);
    }
}
