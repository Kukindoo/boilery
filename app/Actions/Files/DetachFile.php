<?php

namespace App\Actions\Files;

use App\Contracts\OwnsFiles;
use App\Jobs\Files\DeleteStoredFile;
use App\Models\File;
use App\Traits\LogsActionActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Throwable;

class DetachFile
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(Model&OwnsFiles $parent, File $file): void
    {
        try {
            DB::transaction(function () use ($parent, $file): void {
                $parent->refreshForUpdate();
                Gate::authorize('view', $parent);
                $attachedFile = $parent->files()->lockForUpdate()->findOrFail($file->id);
                Gate::authorize('detach', [$attachedFile, $parent]);
                $parent->files()->detach($attachedFile->id);
                $this->logActionCompleted('files', $attachedFile, 'File detached', [
                    'fileable_type' => $parent->getMorphClass(),
                    'fileable_id' => $parent->getKey(),
                ]);

                if ($attachedFile->attachments()->lockForUpdate()->first(['fileables.id']) === null) {
                    $disk = config('filesystems.default');
                    if (! $attachedFile->delete()) {
                        throw new RuntimeException('Could not delete the orphaned file record.');
                    }
                    $this->logActionCompleted('files', $attachedFile, 'Orphaned file deleted', [
                        'path' => $attachedFile->path,
                        'disk' => $disk,
                    ]);
                    DeleteStoredFile::dispatch($attachedFile->path, $disk)->onQueue('default')->afterCommit();
                }
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('files', $file, $exception);

            throw $exception;
        }
    }
}
