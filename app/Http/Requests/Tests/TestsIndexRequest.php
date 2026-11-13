<?php

namespace App\Http\Requests\Tests;

use App\Enums\Roles;
use Illuminate\Foundation\Http\FormRequest;

class TestsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(Roles::ADMIN->value);
    }
}
