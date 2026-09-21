<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\File;
use App\Models\RequestedQuotes;
use Exception;

class UploadQuoteFile
{
    /**
     * @throws Exception
     */
    public function handle($file,
        RequestedQuotes $quote,
        FileTypes $fileType)
    {
        $directory = str_replace(
            '{quote_id}',
            $quote->id,
            config('app.files.quote_save_directory')
        );

        $path = $file->store($directory);

        $extension = $file->getClientOriginalExtension();

        $fileLabel = app(CreateFileLabel::class)->handle(
            $quote,
            $fileType,
            $extension,
        );

        return File::create([
            'path' => $path,
            'quote_id' => $quote->id,
            'original_name' => $file->getClientOriginalName(),
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'file_type' => $fileType->value,
            'size' => $file->getSize(),
            'label' => $fileLabel,
        ]);
    }
}
