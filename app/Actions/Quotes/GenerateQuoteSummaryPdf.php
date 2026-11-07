<?php

namespace App\Actions\Quotes;

use App\Actions\Files\GenerateFileLabel;
use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Quote;
use App\Traits\LogsActionActivity;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\LaravelPdf\Facades\Pdf;
use Throwable;

readonly class GenerateQuoteSummaryPdf
{
    use LogsActionActivity;

    public function __construct(private GenerateFileLabel $generateFileLabel) {}

    /**
     * @throws Throwable
     */
    public function handle(Quote $quote): File
    {
        try {
            $fileType = FileTypes::QUOTE_SUMMARY;
            $fileLabel = $this->generateFileLabel->handle($quote->user, $quote->id, $fileType, 'pdf');

            $attachments = $this->getAttachments($quote);

            $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'))->format('d. m. Y H:i:s T');

            $content = Pdf::view('quotes.pdfs.summary', [
                'quote' => $quote,
                'attachments' => $attachments,
                'generatedAt' => $generatedAt,
            ])->format('a4')
                ->generatePdfContent();

            $disk = Storage::disk();
            $directory = config('app.files.attachments_save_directory');
            $path = mb_rtrim($directory, '/') . '/' . Str::uuid() . '.pdf';
            if (! $disk->put($path, $content)) {
                throw new RuntimeException('Could not store the quote summary PDF.');
            }

            try {
                $file = DB::transaction(function () use ($quote, $path, $fileLabel, $fileType, $content): File {
                    $file = $quote->files()->create([
                        'path' => $path,
                        'original_name' => $fileLabel,
                        'extension' => 'pdf',
                        'mime_type' => 'application/pdf',
                        'file_type' => $fileType->value,
                        'size' => mb_strlen($content, '8bit'),
                        'label' => $fileLabel,
                    ]);
                    $this->logActionCompleted('quotes', $quote, 'Quote summary PDF generated', ['file_id' => $file->id]);

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
            $this->logActionAttempt('quotes', $quote, $exception);

            throw $exception;
        }
    }

    public function getAttachments(Quote $quote): \Illuminate\Support\Collection|Collection
    {
        $disk = Storage::disk();

        return $quote->files()
            ->where('file_type', '!=', FileTypes::QUOTE_SUMMARY->value)
            ->orderBy('files.id')
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
    }
}
