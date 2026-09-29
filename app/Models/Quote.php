<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property string $status
 * @property string $first_name
 * @property string $last_name
 * @property int $user_id
 * @property string $email
 * @property string $phone
 * @property ?string $address
 * @property string $message
 * @property bool $under_warranty
 * @property ?string $boiler_manufacturer
 * @property ?string $boiler_serial_number
 * @property ?string $boiler_type
 */
class Quote extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'status',
        'first_name',
        'last_name',
        'user_id',
        'email',
        'phone',
        'address',
        'message',
        'under_warranty',
        'boiler_manufacturer',
        'boiler_serial_number',
        'boiler_type',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * @return HasMany<File, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'quote_id');
    }

    protected function casts(): array
    {
        return [
            'under_warranty' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isClosed(): bool
    {
        return $this->status === QuoteStatus::ACCEPTED
            or $this->status === QuoteStatus::REJECTED;
    }
}
