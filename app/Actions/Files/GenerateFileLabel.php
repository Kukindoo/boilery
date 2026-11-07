<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateFileLabel
{
    public function handle(User $user,
        int $id,
        FileTypes $fileType,
        ?string $extension = null
    ): string {

        $clean_name = Str::ascii(Str::replace([' ', '-'], '_', $user->name));

        $fileLabelDirty = implode('_', [
            $clean_name,
            $id,
            $fileType->snake(),
        ]);

        return Str::ascii($fileLabelDirty) . ($extension ? '.' . $extension : '');
    }
}
