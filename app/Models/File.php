<?php

namespace App\Models;

use App\Contracts\OwnsFiles;
use App\Enums\FileTypes;
use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $original_name
 * @property FileTypes $file_type
 * @property string $extension
 * @property int $size
 * @property string $mime_type
 * @property string $path
 * @property ?string $label
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'original_name',
        'file_type',
        'extension',
        'mime_type',
        'path',
        'label',
        'size',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('files')
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /** @return HasMany<FileAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(FileAttachment::class);
    }

    /** @return MorphToMany<Issue, $this, FileAttachment> */
    public function issues(): MorphToMany
    {
        return $this->morphedByMany(Issue::class, 'fileable')->using(FileAttachment::class)->withTimestamps();
    }

    /** @return MorphToMany<User, $this, FileAttachment> */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'fileable')->using(FileAttachment::class)->withTimestamps();
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->attachments()->with('fileable')->get()->contains(
            fn (FileAttachment $attachment): bool => $attachment->fileable instanceof OwnsFiles
                && $attachment->fileable->isFileOwner($user),
        );
    }

    /**
     * Determine whether the user owns every model attached to this shared file.
     *
     * Requires at least one attachment. Returns false if any attached model
     * is missing, does not implement OwnsFiles, or belongs to another user.
     * Used to authorize global changes that affect all attachments.
     */
    public function isExclusivelyOwnedBy(User $user): bool
    {
        $attachments = $this->attachments()->with('fileable')->get();

        return $attachments->isNotEmpty() && $attachments->every(
            fn (FileAttachment $attachment): bool => $attachment->fileable instanceof OwnsFiles
                && $attachment->fileable->isFileOwner($user),
        );
    }

    public function isAttachedTo(Model $model): bool
    {
        if ($model instanceof Quote || $model instanceof Work) {
            $model = $model->issue;
        }

        return $this->attachments()->where('fileable_type', $model->getMorphClass())
            ->where('fileable_id', $model->getKey())->exists();
    }

    protected function casts(): array
    {
        return [
            'file_type' => FileTypes::class,
        ];
    }
}
