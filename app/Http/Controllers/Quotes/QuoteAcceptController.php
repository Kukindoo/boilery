<?php

namespace App\Http\Controllers\Quotes;

use App\Actions\Quotes\AcceptQuote;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Throwable;

class QuoteAcceptController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(Quote $quote, AcceptQuote $acceptQuote): RedirectResponse
    {
        $work = $acceptQuote->handle($quote);

        if (Gate::allows('view', $work)) {
            return redirect()->route('works.show', $work);
        }

        if (Gate::allows('view', $quote)) {
            return redirect()->route('quotes.show', $quote);
        }

        return redirect()->route('home');
    }
}
