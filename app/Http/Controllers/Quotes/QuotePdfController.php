<?php

namespace App\Http\Controllers\Quotes;

use App\Actions\Quotes\GeneratePdf;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use Throwable;

class QuotePdfController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(Quote $quote)
    {
        if (! auth()->user()->can('generatePdf', $quote)) {
            abort(403);
        }

        app(GeneratePdf::class)->handle($quote);

        return redirect()->route('quotes.show', $quote);
    }
}
