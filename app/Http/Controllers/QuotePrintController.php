<?php

namespace App\Http\Controllers;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Http\Requests\Quotes\QuotePrintShowRequest;
use App\Models\Quote;
use Exception;

class QuotePrintController
{
    /**
     * @throws Exception
     */
    public function show(QuotePrintShowRequest $request, Quote $quote)
    {
        $fileLabel = app(GenerateFileLabel::class)->handle($quote, FileTypes::QUOTE_SUMMARY);

        return view('quotes.quote-pdf-first-page', [
            'quote' => $quote,
            'fileLabel' => $fileLabel,
        ]);
    }
}
