<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
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
 * @property ?string $label_file_path
 * @property ?string $warranty_file_path
 * @property ?string $receipt_file_path
 */
class RequestedQuotes extends Model
{
    use LogsActivity;
    protected $fillable = [
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
        'label_file_path',
        'warranty_file_path',
        'receipt_file_path',
    ];

    protected function casts(): array
    {
        return [
            'under_warranty' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty();
    }
}
