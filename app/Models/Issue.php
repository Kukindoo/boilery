<?php

namespace App\Models;

use App\Enums\IssueStatus;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property IssueStatus $status
 * @property int $user_id
 * @property ?Carbon $started_at
 * @property ?Carbon $finished_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property ?Carbon $deleted_at
 */
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => IssueStatus::OPEN->value,
    ];

    protected $fillable = ['user_id', 'status', 'started_at', 'finished_at'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Quote, $this> */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /** @return HasMany<Work, $this> */
    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    protected function casts(): array
    {
        return [
            'status' => IssueStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
