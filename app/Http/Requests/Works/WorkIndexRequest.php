<?php

namespace App\Http\Requests\Works;

use App\Models\Work;
use Illuminate\Foundation\Http\FormRequest;

class WorkIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Work::class);
    }
}
