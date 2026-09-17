<?php

namespace App\Http\Controllers;

use App\Enums\BoilerManufacturers;
use App\Models\RequestedQuotes;

class QuoteController extends Controller
{
    public function index()
    {
        return view('quotes.index');
    }

    public function show(RequestedQuotes $quote)
    {
        $manufacturer = BoilerManufacturers::tryFrom($quote->boiler_manufacturer);

        if (is_null($manufacturer)) {
            $manufacturer = BoilerManufacturers::UNKNOWN;
        }

        return view('quotes.show', [
            'quote' => $quote,
            'manufacturer' => $manufacturer,
        ]);
    }
}
