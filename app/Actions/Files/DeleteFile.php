<?php

namespace App\Actions\Files;

use App\Jobs\Files\DeleteStoredFile;
use App\Models\File;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
            DB::transaction(function () use ($file): void {
                $file->refreshForUpdate();
                Gate::authorize('delete', $file);

                if (! $file->delete()) {
                    throw new RuntimeException('Could not delete the file record.');
                }

                $disk = config('filesystems.default');
                $this->logActionCompleted('files', $file, 'File deleted', [
                    'path' => $file->path,
                    'disk' => $disk,
                ]);

                DeleteStoredFile::dispatch($file->path, $disk)->onQueue('default')->afterCommit();
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('files', $file, $exception);

            throw $exception;
        }
    }
}
