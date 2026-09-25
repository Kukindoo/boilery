<?php

namespace App\Models;

use App\Enums\FileTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quote_id
 * @property string $original_name
 * @property string $file_type
 * @property string $extension
 * @property int $size
 * @property string $mime_type
 * @property string $path
 * @property string $label
 */
class File extends Model
{
    protected $fillable = [
        'original_name',
        'file_type',
        'extension',
        'mime_type',
        'path',
        'label',
        'quote_id',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'file_type' => FileTypes::class,
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'id');
    }
}
