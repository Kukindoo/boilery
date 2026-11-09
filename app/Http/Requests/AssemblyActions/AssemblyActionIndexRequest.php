<?php

namespace App\Http\Requests\AssemblyActions;

use App\Models\AssemblyAction;
use Illuminate\Foundation\Http\FormRequest;

class AssemblyActionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', AssemblyAction::class);
    }
}
