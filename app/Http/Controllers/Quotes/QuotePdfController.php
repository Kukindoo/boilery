<?php

namespace App\Http\Controllers\Quotes;

use App\Actions\Pdf\QueuePdf;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;

class QuotePdfController extends Controller
{
    public function __invoke(Quote $quote, QueuePdf $queuePdf): RedirectResponse
    {
        $queuePdf->handle($quote);

        return redirect()->route('quotes.show', $quote);
    }
}
