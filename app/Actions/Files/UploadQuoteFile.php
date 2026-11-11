<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\File;
use App\Models\Quote;
use Illuminate\Http\UploadedFile;
use Throwable;

readonly class UploadQuoteFile
{
    public function __construct(private UploadFile $uploadFile) {}

    /**
     * Uploads performed during trusted public quote creation.
     *
     * @throws Throwable
     */
    public function handle(UploadedFile $file, Quote $quote, FileTypes $fileType): File
    {
        return $this->uploadFile->forPublicQuote($file, $quote, $fileType);
    }
}
