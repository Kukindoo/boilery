<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property string $status
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property ?string $address
 * @property string $message
 * @property bool $under_warranty
 * @property ?string $boiler_manufacturer
 * @property ?string $boiler_serial_number
 * @property ?string $boiler_type
 */
class RequestedQuotes extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'status',
        'first_name',
        'last_name',
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
}
