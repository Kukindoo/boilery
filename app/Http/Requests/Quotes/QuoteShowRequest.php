<?php

namespace App\Http\Requests\Quotes;

use App\Enums\Permissions;
use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;

class QuoteShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Quote $quote */
        $quote = $this->route('quote');

        return $this->user()->hasPermissionTo(Permissions::QUOTE_VIEW_ALL) or
            ($this->user()->hasPermissionTo(Permissions::QUOTE_VIEW_OWN)
                and $quote->user_id === $this->user()->id);
    }
}
