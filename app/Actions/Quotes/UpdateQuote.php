<?php

namespace App\Actions\Quotes;

use App\Dtos\Quotes\QuoteDto;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class UpdateQuote
{
    /**
     * @throws Throwable
     */
    public function handle(Quote $quote, QuoteDto $data): Quote
    {
        Gate::authorize('update', $quote);

        $attributes = $data->toArray();
        $reassignment = array_intersect_key($attributes, array_flip([
            'user_id',
        ]));

        $quote->update(array_diff_key($attributes, $reassignment));

        return $quote;
    }
}
