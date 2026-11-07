<?php

namespace App\Actions\Works;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Work;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

readonly class GenerateServiceProtocolPdf
{
    use LogsActionActivity;

    public function __construct(private GenerateFileLabel $generateFileLabel) {}

    /**
     * @throws Throwable
     */
    public function handle(Work $work): File
    {
        try {
            $fileType = FileTypes::SERVICE_PROTOCOL;
            $fileLabel = $this->generateFileLabel->handle($work->user, $work->id, $fileType, 'pdf');

            $content = Pdf::view('pdfs.service-protocol-pdf', [
                'work' => $work,
                'customer' => [
                    'name' => $work->user?->name,
                    'address' => $work->user?->address,
                    'phone' => $work->user?->phone,
                ],
            ])->format('a4')
                ->generatePdfContent();

            $disk = Storage::disk();
            $directory = config('app.files.attachments_save_directory');
            $path = mb_rtrim($directory, '/') . '/' . Str::uuid() . '.pdf';
            if (! $disk->put($path, $content)) {
                throw new RuntimeException('Could not store the service protocol PDF.');
            }

            try {
                $file = DB::transaction(function () use ($work, $path, $fileLabel, $fileType, $content): File {
                    $file = $work->files()->create([
                        'path' => $path,
                        'original_name' => $fileLabel,
                        'extension' => 'pdf',
                        'mime_type' => 'application/pdf',
                        'file_type' => $fileType->value,
                        'size' => mb_strlen($content, '8bit'),
                        'label' => $fileLabel,
                    ]);
                    $this->logActionCompleted('works', $work, 'Service protocol PDF generated', ['file_id' => $file->id]);

                    return $file;
                }, attempts: 5);
            } catch (Throwable $exception) {
                $disk->delete($path);

                throw $exception;
            }

            if (DB::transactionLevel() > 0) {
                DB::afterRollBack(fn () => $disk->delete($path));
            }

            return $file;
        } catch (Throwable $exception) {
            $this->logActionAttempt('works', $work, $exception);

            throw $exception;
        }
    }
}
