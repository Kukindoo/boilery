<?php

namespace App\Actions\Files;

use App\Enums\FileTypes;
use App\Models\File;
use App\Traits\LogsActionActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class ChangeFileType
{
    use LogsActionActivity;

    /**
     * @throws Throwable
     */
    public function handle(File $file, FileTypes $fileType): File
    {
        try {
            return DB::transaction(function () use ($file, $fileType): File {
                $lockedFile = File::query()->lockForUpdate()->findOrFail($file->id);
                Gate::authorize('changeType', $lockedFile);

                $previousType = $lockedFile->file_type;
                $lockedFile->update(['file_type' => $fileType]);
                $this->logActionCompleted('files', $lockedFile, 'File type changed', [
                    'previous_type' => $previousType->value,
                    'file_type' => $fileType->value,
                ]);

                return $lockedFile;
            }, attempts: 5);
        } catch (Throwable $exception) {
            $this->logActionAttempt('files', $file, $exception);

            throw $exception;
        }
    }
}
