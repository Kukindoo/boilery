<?php

namespace App\Actions\Pdf;

use App\Contracts\OwnsFiles;
use App\Enums\PdfGenerationStatus;
use App\Jobs\Pdf\GeneratePdf;
use App\Models\Quote;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

class QueuePdf
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Model&OwnsFiles $model): void
    {
        $logName = match (true) {
            $model instanceof Quote => 'quotes',
            $model instanceof Work => 'works',
            default => null,
        };

        if ($logName === null) {
            abort(404);
        }

        $generationId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($model, $generationId, $logName): void {
                $lockedModel = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());
                Gate::authorize('generatePdf', $lockedModel);

                if (in_array($lockedModel->pdf_generation_status, [PdfGenerationStatus::QUEUED, PdfGenerationStatus::PROCESSING], true)) {
                    return;
                }

                $lockedModel->pdf_generation_status = PdfGenerationStatus::QUEUED;
                $lockedModel->pdf_generation_id = $generationId;
                $lockedModel->save();
                $this->logActionCompleted($logName, $lockedModel, 'PDF generation queued', ['generation_id' => $generationId]);

                GeneratePdf::dispatch(
                    match (true) {
                        $lockedModel instanceof Quote => 'quote',
                        $lockedModel instanceof Work => 'work',
                    },
                    $lockedModel->getKey(),
                    $generationId,
                )->onQueue('default')->afterCommit();
            });
        } catch (Throwable $exception) {
            $model->newQuery()->whereKey($model->getKey())
                ->where('pdf_generation_id', $generationId)
                ->update(['pdf_generation_status' => PdfGenerationStatus::FAILED]);

            $this->logActionAttempt($logName, $model, $exception, ['generation_id' => $generationId]);

            throw $exception;
        }
    }
}
