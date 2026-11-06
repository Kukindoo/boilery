<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Quote;
use App\Traits\LogsActionActivity;
use Throwable;

class UploadQuoteFile
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle($file,
        Quote $quote,
        FileTypes $fileType)
    {
        try {
            $directory = config('app.files.attachments_save_directory');

            $path = $file->store($directory);

            $extension = $file->getClientOriginalExtension();

            $fileLabel = app(GenerateFileLabel::class)->handle(
                $quote->user,
                $quote->id,
                $fileType,
                $extension,
            );

            $storedFile = File::create([
                'path' => $path,
                'quote_id' => $quote->id,
                'original_name' => $file->getClientOriginalName(),
                'extension' => $extension,
                'mime_type' => $file->getMimeType(),
                'file_type' => $fileType->value,
                'size' => $file->getSize(),
                'label' => $fileLabel,
            ]);

            $this->logActionCompleted('files', $storedFile, 'File uploaded');

            return $storedFile;
        } catch (Throwable $exception) {
            $this->logActionAttempt('files', null, $exception, ['quote_id' => $quote->id]);

            throw $exception;
        }
    }
}
