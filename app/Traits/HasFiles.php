<?php

namespace App\Traits;

use App\Jobs\Files\DeleteStoredFile;
use App\Models\File;
use App\Models\FileAttachment;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use RuntimeException;
use Throwable;

trait HasFiles
{
    /**
     * Keep parent deletion and attachment cleanup in the same transaction.
     *
     * @throws Throwable
     */
    public function delete(): ?bool
    {
        return $this->getConnection()->transaction(fn (): ?bool => parent::delete());
    }

    /** @return MorphToMany<File, $this, FileAttachment> */
    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'fileable')
            ->using(FileAttachment::class)
            ->withTimestamps();
    }

    protected static function bootHasFiles(): void
    {
        static::deleted(function (self $model): void {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $files = $model->files()->orderBy('files.id')->lockForUpdate()->get();
                $model->files()->detach();

                foreach ($files as $file) {
                    /** @var File $file */
                    if ($file->attachments()->lockForUpdate()->first(['fileables.id']) !== null) {
                        continue;
                    }

                    if (! $file->delete()) {
                        throw new RuntimeException('Could not delete the orphaned file record.');
                    }

                    DeleteStoredFile::dispatch($file->path, config('filesystems.default'))
                        ->onQueue('default')->afterCommit();
                }
            }
        });
    }
}
