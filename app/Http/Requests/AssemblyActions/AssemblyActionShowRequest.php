<?php

namespace App\Http\Requests\AssemblyActions;

use Illuminate\Foundation\Http\FormRequest;

class AssemblyActionShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('assembly_action'));
    }
}
