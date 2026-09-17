<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\File;
use App\Models\RequestedQuotes;
use Illuminate\Support\Str;

class UploadFile
{
    public function handle($file,
        RequestedQuotes $quote,
        FileTypes $fileType)
    {
        $path = $file->store('files');

        $extension = $file->getClientOriginalExtension();

        $fileNameDirty = implode('_', [
            $quote->first_name,
            $quote->last_name,
            $quote->id,
            $fileType->snake(),
        ]);

        $fileName = Str::ascii($fileNameDirty) . '.' . $extension;

        return File::create([
            'path' => $path,
            'quote_id' => $quote->id,
            'original_name' => $file->getClientOriginalName(),
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'file_type' => $fileType->value,
            'size' => $file->getSize(),
            'label' => $fileName,
        ]);
    }
}
