<?php

namespace App\Http\Controllers;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Models\Quote;

class QuotePrintController
{
    public function show(Quote $quote)
    {
        $fileLabel = app(GenerateFileLabel::class)->handle($quote, FileTypes::QUOTE_SUMMARY);

        return view('quotes.quote-pdf-first-page', [
            'quote' => $quote,
            'fileLabel' => $fileLabel
        ]);
    }
}