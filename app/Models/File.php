<?php

namespace App\Models;

use App\Enums\FileTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ?int $quote_id
 * @property ?int $work_id
 * @property string $original_name
 * @property FileTypes $file_type
 * @property string $extension
 * @property int $size
 * @property string $mime_type
 * @property string $path
 * @property string $label
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
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
        'work_id',
        'size',
    ];

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'id');
    }

    /** @return BelongsTo<Work, $this> */
    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class, 'work_id');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->quote?->user_id === $user->id
            || $this->work?->user_id === $user->id;
    }

    protected function casts(): array
    {
        return [
            'file_type' => FileTypes::class,
        ];
    }
}
