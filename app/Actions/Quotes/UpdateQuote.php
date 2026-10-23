<?php

namespace App\Actions\Quotes;

use App\Dtos\Quotes\QuoteDto;
use App\Enums\Permissions;
use App\Models\Quote;
use Illuminate\Support\Facades\Gate;

class UpdateQuote
{
    public function handle(Quote $quote, QuoteDto $data): Quote
    {
        Gate::authorize('update', $quote);

        $quote->update($data->toArray());

        return $quote;
    }
}
