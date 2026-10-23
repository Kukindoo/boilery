<?php

namespace App\Actions\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\Gate;

class RestoreQuote
{
    public function handle(Quote $quote): void
    {
        Gate::authorize('restore', $quote);
        $quote->restore();
    }
}
