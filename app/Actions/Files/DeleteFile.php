<?php

namespace App\Actions\Files;

use App\Models\File;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DeleteFile
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(File $file): void
    {
        try {
            Gate::authorize('delete', $file);

            if (! Storage::disk()->delete($file->path)) {
                throw new RuntimeException('Could not delete the stored file.');
            }

            $file->delete();
            $this->logActionCompleted('files', $file, 'File deleted');
        } catch (Throwable $exception) {
            $this->logActionAttempt('files', $file, $exception);

            throw $exception;
        }
    }
}
