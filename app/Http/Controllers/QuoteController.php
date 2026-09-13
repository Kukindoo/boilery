<?php

namespace App\Http\Controllers;

class QuoteController extends Controller
{
    public function index()
    {
        return view('quotes.index');
    }
}
