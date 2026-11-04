<?php

namespace App\Actions\Quotes;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Quote;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

readonly class GeneratePdf
{
    public function __construct(private GenerateFileLabel $generateFileLabel) {}

    /**
     * @throws Throwable
     */
    public function handle(Quote $quote): File
    {
        $fileType = FileTypes::QUOTE_SUMMARY;
        $fileLabel = $this->generateFileLabel->handle($quote, $fileType, 'pdf');
        $directory = str_replace('{quote_id}', (string) $quote->id, config('app.files.quote_save_directory'));
        $path = mb_rtrim($directory, '/') . '/' . Str::uuid() . '.pdf';

        $disk = Storage::disk();
        $attachments = $quote->files()
            ->where('file_type', '!=', FileTypes::QUOTE_SUMMARY->value)
            ->orderBy('id')
            ->get()
            ->map(function (File $file) use ($disk): array {
                $image = null;

                if (in_array($file->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'], true)) {
                    $imageContent = $disk->get($file->path);

                    if ($imageContent === null) {
                        throw new RuntimeException('Could not read quote attachment: ' . $file->label);
                    }

                    $image = 'data:' . $file->mime_type . ';base64,' . base64_encode($imageContent);
                }

                return [
                    'label' => $file->label,
                    'type' => $file->file_type->label(),
                    'mime_type' => $file->mime_type,
                    'image' => $image,
                ];
            });

        $content = Pdf::view('quotes.pdfs.summary', ['quote' => $quote, 'attachments' => $attachments])
            ->format('a4')
            ->generatePdfContent();

        if (! $disk->put($path, $content)) {
            throw new RuntimeException('Could not store the quote summary PDF.');
        }

        try {
            return $quote->files()->create([
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
