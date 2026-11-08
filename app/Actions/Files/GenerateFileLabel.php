<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateFileLabel
{
    public function handle(User $user, FileTypes $fileType, File $file): string
    {
        $extension = $file->extension;
        $customerName = Str::ascii(Str::replace([' ', '-'], '_', $user->name));
        $prefix = implode('_', [
            now()->timezone('Europe/Prague')->format('Y-m-d_H-i'),
            $customerName,
            $fileType->snake(),
        ]);

        do {
            $suffix = Str::lower(Str::random(4));

            $label = $prefix . '_' . $suffix . ($extension ? '.' . $extension : '');
        } while (File::query()->where('label', $label)->exists());

        return $label;
    }
}
