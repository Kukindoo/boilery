<?php

namespace App\Actions\Files;

use App\Models\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteFile
{
    public function handle(File $file): void
    {
        Gate::authorize('delete', $file);

        if (! Storage::disk()->delete($file->path)) {
            throw new RuntimeException('Could not delete the stored file.');
        }

        $file->delete();
    }
}
