<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FileAttachment extends MorphPivot
{
    public $incrementing = true;

    protected $table = 'fileables';

    protected $fillable = ['file_id', 'fileable_type', 'fileable_id'];

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /** @return MorphTo<Model, $this> */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}
