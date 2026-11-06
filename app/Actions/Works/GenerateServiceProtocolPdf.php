<?php

namespace App\Actions\Works;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Work;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

readonly class GenerateServiceProtocolPdf
{
    public function __construct(private GenerateFileLabel $generateFileLabel) {}

    /**
     * @throws Throwable
     */
    public function handle(Work $work): File
    {
        $content = Pdf::view('works.pdfs.service-protocol', [
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

        $fileType = FileTypes::SERVICE_PROTOCOL;
        $fileLabel = $this->generateFileLabel->handle($work->user, $work->id, $fileType, 'pdf');
        try {
            return $work->files()->create([
                'path' => $path,
                'original_name' => $fileLabel,
                'extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'file_type' => $fileType->value,
                'size' => mb_strlen($content, '8bit'),
                'label' => $fileLabel,
            ]);
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }
    }
}
