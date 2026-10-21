<?php

namespace App\Http\Requests\Works;

use Illuminate\Foundation\Http\FormRequest;

class WorkEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('work'));
    }
}
