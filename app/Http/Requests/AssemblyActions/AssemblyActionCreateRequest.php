<?php

namespace App\Http\Requests\AssemblyActions;

use App\Models\AssemblyAction;
use Illuminate\Foundation\Http\FormRequest;

class AssemblyActionCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AssemblyAction::class);
    }
}
