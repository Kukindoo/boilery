<?php

namespace App\Actions\Works;

use App\Enums\PdfGenerationStatus;
use App\Jobs\Works\GenerateServiceProtocolPdf;
use App\Models\Work;
use App\Traits\LogsActionActivity;
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
    public function handle(Work $work): void
    {
        $generationId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($work, $generationId): void {
                $lockedWork = Work::query()->lockForUpdate()->findOrFail($work->id);
                Gate::authorize('generatePdf', $lockedWork);

                if ($lockedWork->pdfGenerationInProgress()) {
                    return;
                }

                $lockedWork->pdf_generation_status = PdfGenerationStatus::QUEUED;
                $lockedWork->pdf_generation_id = $generationId;
                $lockedWork->save();
                $this->logActionCompleted('works', $lockedWork, 'PDF generation queued', ['generation_id' => $generationId]);

                GenerateServiceProtocolPdf::dispatch($lockedWork->id, $generationId)->onQueue('default')->afterCommit();
            });
        } catch (Throwable $exception) {
            Work::query()->whereKey($work->id)
                ->where('pdf_generation_id', $generationId)
                ->update(['pdf_generation_status' => PdfGenerationStatus::FAILED]);

            $this->logActionAttempt('works', $work, $exception, ['generation_id' => $generationId]);

            throw $exception;
        }
    }
}
