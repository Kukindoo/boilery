<?php

namespace App\Http\Controllers;

use App\Models\RequestedQuotes;

class QuotePrintController
{
    public function show(RequestedQuotes $quote)
    {
        return view('quotes.quote-pdf-first-page', [
            'quote' => $quote,
        ]);
    }
}