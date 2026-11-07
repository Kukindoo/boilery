<?php

namespace App\Traits;

use App\Models\File;
use App\Models\FileAttachment;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasFiles
{
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
                $model->files()->detach();
            }
        });
    }
}
