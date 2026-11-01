<?php

namespace App\Http\Controllers;

use App\Actions\Quotes\AcceptQuote;
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
        $quote = $acceptQuote->handle($quote);

        if ($quote->work !== null && Gate::allows('view', $quote->work)) {
            return redirect()->route('works.show', $quote->work);
        }

        if (Gate::allows('view', $quote)) {
            return redirect()->route('quotes.show', $quote);
        }

        return redirect()->route('home');
    }
}
