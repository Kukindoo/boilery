<?php

namespace App\Jobs\Quotes;

use App\Actions\Quotes\GeneratePdf;
use App\Enums\QuotePdfStatus;
use App\Models\Quote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateQuotePdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public int $quoteId, public string $generationId) {}

    /**
     * @throws Throwable
     */
    public function handle(GeneratePdf $generatePdf): void
    {
        $claimed = Quote::query()->whereKey($this->quoteId)
            ->where('pdf_generation_id', $this->generationId)
            ->whereIn('pdf_generation_status', [QuotePdfStatus::QUEUED, QuotePdfStatus::FAILED])
            ->update(['pdf_generation_status' => QuotePdfStatus::PROCESSING]);

        if (! $claimed) {
            return;
        }

        $quote = Quote::find($this->quoteId);

        if ($quote === null) {
            return;
        }

        if (! $quote->hasQuoteSummary()) {
            $generatePdf->handle($quote);
        }

        Quote::query()->whereKey($this->quoteId)
            ->where('pdf_generation_id', $this->generationId)
            ->update(['pdf_generation_status' => QuotePdfStatus::COMPLETED]);
    }

    public function failed(?Throwable $exception): void
    {
        $quote = Quote::query()->where('pdf_generation_id', $this->generationId)->find($this->quoteId);

        if ($quote === null) {
            return;
        }

        Quote::query()->whereKey($this->quoteId)
            ->where('pdf_generation_id', $this->generationId)
            ->update(['pdf_generation_status' => $quote->hasQuoteSummary() ? QuotePdfStatus::COMPLETED : QuotePdfStatus::FAILED]);
    }
}
