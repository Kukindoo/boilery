<?php

namespace App\Http\Requests\Issues;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;

class IssueCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return ['customer_id' => ['nullable', 'integer', 'exists:users,id']];
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', Issue::class);
    }
}
