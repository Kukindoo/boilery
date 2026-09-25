<?php

namespace App\Http\Requests\Quotes;

use App\Enums\Permissions;
use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;

class QuoteIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view-any', Quote::class);
    }
}
