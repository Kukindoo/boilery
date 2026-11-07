<?php

namespace App\Http\Controllers\Quotes;

use App\Enums\FileTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Quotes\QuoteIndexRequest;
use App\Http\Requests\Quotes\QuoteShowRequest;
use App\Models\File;
use App\Models\Quote;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Throwable;

class QuoteController extends Controller
{
    public function index(QuoteIndexRequest $request)
    {
        return view('quotes.index');
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     */
    public function show(QuoteShowRequest $request, Quote $quote): View
    {
        $quote->load(['user', 'files']);

        $attachments = $quote->files
            ->filter(fn (File $file): bool => $file->file_type !== FileTypes::QUOTE_SUMMARY && Gate::allows('view', $file))
            ->map(fn (File $file): array => [
                'label' => $file->label,
                'type' => $file->file_type->label(),
                'mime_type' => $file->mime_type,
                'image' => str_starts_with($file->mime_type, 'image/')
                    ? route('files.preview', ['quote', $quote, $file])
                    : null,
            ]);

        $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'))->format('d. m. Y H:i:s T');

        $pdfContent = view('pdfs.quote-summary-pdf', [
            'quote' => $quote,
            'attachments' => $attachments,
            'generatedAt' => $generatedAt,
        ])->render();

        return view('quotes.show', [
            'quote' => $quote,
            'pdfContent' => $pdfContent,
        ]);
    }
}
