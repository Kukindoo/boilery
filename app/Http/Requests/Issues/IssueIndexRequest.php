<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;

class IssueIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Issue::class);
    }
}
