<?php

namespace App\Jobs\Works;

use App\Actions\Works\GenerateServiceProtocolPdf as GeneratePdfAction;
use App\Enums\PdfGenerationStatus;
use App\Models\Work;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateServiceProtocolPdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public int $workId, public string $generationId) {}

    /**
     * @throws Throwable
     */
    public function handle(GeneratePdfAction $generatePdf): void
    {
        $claimed = Work::query()->whereKey($this->workId)
            ->where('pdf_generation_id', $this->generationId)
            ->whereIn('pdf_generation_status', [PdfGenerationStatus::QUEUED, PdfGenerationStatus::FAILED])
            ->update(['pdf_generation_status' => PdfGenerationStatus::PROCESSING]);

        if (! $claimed) {
            return;
        }

        $work = Work::find($this->workId);

        if ($work === null) {
            return;
        }

        if (! $work->hasServiceProtocol()) {
            $generatePdf->handle($work);
        }

        Work::query()->whereKey($this->workId)
            ->where('pdf_generation_id', $this->generationId)
            ->update(['pdf_generation_status' => PdfGenerationStatus::COMPLETED]);
    }

    public function failed(?Throwable $exception): void
    {
        $work = Work::query()->where('pdf_generation_id', $this->generationId)->find($this->workId);

        if ($work === null) {
            return;
        }

        Work::query()->whereKey($this->workId)
            ->where('pdf_generation_id', $this->generationId)
            ->update(['pdf_generation_status' => $work->hasServiceProtocol() ? PdfGenerationStatus::COMPLETED : PdfGenerationStatus::FAILED]);
    }
}
