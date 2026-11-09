<?php

namespace App\Http\Requests\AssemblyActions;

use Illuminate\Foundation\Http\FormRequest;

class AssemblyActionEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('assembly_action'));
    }
}
