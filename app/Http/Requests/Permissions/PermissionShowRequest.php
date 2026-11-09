<?php

namespace App\Http\Requests\Permissions;

use Illuminate\Foundation\Http\FormRequest;

class PermissionShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('permission'));
    }
}
