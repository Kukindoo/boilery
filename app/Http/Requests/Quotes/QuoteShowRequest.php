<?php

namespace App\Http\Requests\Quotes;

use App\Enums\Permissions;
use Illuminate\Foundation\Http\FormRequest;

class QuoteShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo(Permissions::QUOTE_VIEW_ALL);
    }
}
