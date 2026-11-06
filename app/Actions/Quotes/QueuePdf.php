<?php

namespace App\Actions\Quotes;

use App\Enums\PdfGenerationStatus;
use App\Jobs\Quotes\GenerateQuoteSummaryPdf;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

class QueuePdf
{
    public function handle(Quote $quote): void
    {
        $generationId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($quote, $generationId): void {
                $lockedQuote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
                Gate::authorize('generatePdf', $lockedQuote);

                if ($lockedQuote->pdfGenerationInProgress()) {
                    return;
                }

                $lockedQuote->pdf_generation_status = PdfGenerationStatus::QUEUED;
                $lockedQuote->pdf_generation_id = $generationId;
                $lockedQuote->save();

                GenerateQuoteSummaryPdf::dispatch($lockedQuote->id, $generationId)->onQueue('default')->afterCommit();
            });
        } catch (Throwable $exception) {
            Quote::query()->whereKey($quote->id)
                ->where('pdf_generation_id', $generationId)
                ->update(['pdf_generation_status' => PdfGenerationStatus::FAILED]);

            throw $exception;
        }
    }
}
