<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;

class RoleShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('role'));
    }
}
