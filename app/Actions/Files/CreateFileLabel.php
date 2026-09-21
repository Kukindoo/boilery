<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\RequestedQuotes;
use Illuminate\Support\Str;

class CreateFileLabel
{
    public function handle(RequestedQuotes $quote,
        FileTypes $fileType,
        string $extension
    ): string {

        $fileLabelDirty = implode('_', [
            $quote->first_name,
            $quote->last_name,
            $quote->id,
            $fileType->snake(),
        ]);

        return Str::ascii($fileLabelDirty) . '.' . $extension;
    }
}
