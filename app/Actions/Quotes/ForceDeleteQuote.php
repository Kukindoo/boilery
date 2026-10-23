<?php

namespace App\Actions\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\Gate;

class ForceDeleteQuote
{
    public function handle(Quote $quote): void
    {
        Gate::authorize('forceDelete', $quote);
        $quote->forceDelete();
    }
}
