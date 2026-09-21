<?php

namespace App\Http\Controllers;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Models\RequestedQuotes;

class QuotePrintController
{
    public function show(RequestedQuotes $quote)
    {
        $fileLabel = app(GenerateFileLabel::class)->handle($quote, FileTypes::QUOTE_SUMMARY);

        return view('quotes.quote-pdf-first-page', [
            'quote' => $quote,
            'fileLabel' => $fileLabel
        ]);
    }
}