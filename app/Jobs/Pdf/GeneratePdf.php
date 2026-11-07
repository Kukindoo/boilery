<?php

namespace App\Jobs\Pdf;

use App\Actions\Quotes\GenerateQuoteSummaryPdf;
use App\Actions\Works\GenerateServiceProtocolPdf;
use App\Contracts\OwnsFiles;
use App\Enums\FileTypes;
use App\Enums\PdfGenerationStatus;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GeneratePdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public string $modelType, public int $modelId, public string $generationId) {}

    /** @throws Throwable */
    public function handle(GenerateQuoteSummaryPdf $generateQuotePdf, GenerateServiceProtocolPdf $generateWorkPdf): void
    {
        $modelClass = $this->modelClass();
        $claimed = $modelClass::query()->whereKey($this->modelId)
            ->where('pdf_generation_id', $this->generationId)
            ->whereIn('pdf_generation_status', [PdfGenerationStatus::QUEUED, PdfGenerationStatus::FAILED])
            ->update(['pdf_generation_status' => PdfGenerationStatus::PROCESSING]);

        if (! $claimed) {
            return;
        }

        $model = $modelClass::find($this->modelId);

        if ($model === null) {
            return;
        }

        if (! $this->hasPdf($model)) {
            match (true) {
                $model instanceof Quote => $generateQuotePdf->handle($model),
                $model instanceof Work => $generateWorkPdf->handle($model),
            };
        }

        $modelClass::query()->whereKey($this->modelId)
            ->where('pdf_generation_id', $this->generationId)
            ->update(['pdf_generation_status' => PdfGenerationStatus::COMPLETED]);
    }

    public function failed(?Throwable $exception): void
    {
        $modelClass = $this->modelClass();
        $model = $modelClass::query()->where('pdf_generation_id', $this->generationId)->find($this->modelId);

        if ($model === null) {
            return;
        }

        $modelClass::query()->whereKey($this->modelId)
            ->where('pdf_generation_id', $this->generationId)
            ->update(['pdf_generation_status' => $this->hasPdf($model) ? PdfGenerationStatus::COMPLETED : PdfGenerationStatus::FAILED]);
    }

    /** @return class-string<Quote>|class-string<Work> */
    private function modelClass(): string
    {
        return match ($this->modelType) {
            'quote' => Quote::class,
            'work' => Work::class,
        };
    }

    private function hasPdf(Model&OwnsFiles $model): bool
    {
        $fileType = match ($this->modelType) {
            'quote' => FileTypes::QUOTE_SUMMARY,
            'work' => FileTypes::SERVICE_PROTOCOL,
        };

        return $model->files()->where('file_type', $fileType->value)->exists();
    }
}
