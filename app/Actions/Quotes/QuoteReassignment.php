<?php

namespace App\Actions\Quotes;

use App\Dtos\Quotes\QuoteDto;
use App\Models\Quote;
use Illuminate\Support\Facades\Gate;

class QuoteReassignment
{
    public function handle(Quote $quote, QuoteDto $data): Quote
    {
        Gate::authorize('reassignment', $quote);

        $quote->update([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'user_id' => $data->userId,
        ]);

        return $quote;
    }
}
