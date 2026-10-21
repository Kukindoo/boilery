<?php

namespace App\Http\Requests\Works;

use Illuminate\Foundation\Http\FormRequest;

class WorkShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('work'));
    }
}
