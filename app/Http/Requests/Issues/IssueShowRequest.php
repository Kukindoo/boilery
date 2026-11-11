<?php

namespace App\Http\Requests\Issues;

use Illuminate\Foundation\Http\FormRequest;

class IssueShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('issue'));
    }
}
