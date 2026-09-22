<?php

namespace App\Http\Controllers;

use App\Http\Requests\Quotes\QuoteIndexRequest;
use App\Http\Requests\Quotes\QuoteShowRequest;
use App\Models\RequestedQuotes;

class QuoteController extends Controller
{
    public function index(QuoteIndexRequest $request)
    {
        return view('quotes.index');
    }

    public function show(QuoteShowRequest $request, RequestedQuotes $quote)
    {
        return view('quotes.show', [
            'quote' => $quote,
        ]);
    }
}
