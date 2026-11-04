<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\Quote;
use Illuminate\Support\Str;

class GenerateFileLabel
{
    public function handle(Quote $quote,
        FileTypes $fileType,
        ?string $extension = null
    ): string {

        $fileLabelDirty = implode('_', [
            $quote->first_name,
            $quote->last_name,
            $quote->id,
            $fileType->snake(),
        ]);

        $fileLabel = Str::ascii($fileLabelDirty);

        $extensionSuffix = is_null($extension) ? '' : '.' . $extension;
        $existingLabels = $quote->files()->pluck('label');
        $candidate = $fileLabel . $extensionSuffix;
        $number = 1;

        while ($existingLabels->contains($candidate)) {
            $candidate = $fileLabel . '_' . $number . $extensionSuffix;
            $number++;
        }

        return $candidate;
    }
}
