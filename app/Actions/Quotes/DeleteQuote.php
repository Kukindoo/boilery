<?php

namespace App\Actions\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\Gate;

class DeleteQuote
{
    public function handle(Quote $quote): void
    {
        Gate::authorize('delete', $quote);
        $quote->delete();
    }
}
