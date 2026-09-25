<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\Quote;
use Illuminate\Support\Str;

class GenerateFileLabel
{
    public function handle(Quote     $quote,
                           FileTypes $fileType,
                           ?string   $extension = null
    ): string {

        $fileLabelDirty = implode('_', [
            $quote->first_name,
            $quote->last_name,
            $quote->id,
            $fileType->snake(),
        ]);

        $fileLabel = Str::ascii($fileLabelDirty);

        if (is_null($extension)) {
            return $fileLabel;
        }

        return $fileLabel . '.' . $extension;
    }
}
