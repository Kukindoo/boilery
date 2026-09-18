<?php

namespace App\Http\Controllers;

use App\Models\RequestedQuotes;

class QuoteController extends Controller
{
    public function index()
    {
        return view('quotes.index');
    }

    public function show(RequestedQuotes $quote)
    {
        return view('quotes.show', [
            'quote' => $quote,
        ]);
    }
}
