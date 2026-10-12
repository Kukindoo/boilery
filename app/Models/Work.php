<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $user_id
 * @property ?int $quote_id
 * @property string $order_id
 * @property ?string $type_of_product
 * @property ?string $type_number
 * @property ?string $serial_number
 * @property bool $warranty_accepted
 * @property ?string $repair_in_warranty
 * @property ?Carbon $day_of_purchase
 * @property ?string $building_type
 * @property ?string $reported_problem
 * @property ?Carbon $work_accepted_at
 * @property ?Carbon $work_completed_at
 * @property ?string $work_performed_description
 * @property ?string $transport_from
 * @property ?string $transport_to
 * @property ?int $transport_rate_in_czk
 * @property ?string $transport_distance_in_km
 * @property ?string $note
 * @property ?string $mechanic_name
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
class Work extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'quote_id',
        'order_id',
        'type_of_product',
        'type_number',
        'serial_number',
        'warranty_accepted',
        'repair_in_warranty',
        'day_of_purchase',
        'building_type',
        'reported_problem',
        'work_accepted_at',
        'work_completed_at',
        'work_performed_description',
        'transport_from',
        'transport_to',
        'transport_rate_in_czk',
        'transport_distance_in_km',
        'note',
        'mechanic_name',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    protected function casts(): array
    {
        return [
            'warranty_accepted' => 'boolean',
            'day_of_purchase' => 'date',
            'work_accepted_at' => 'date',
            'work_completed_at' => 'date',
            'transport_rate_in_czk' => 'integer',
            'transport_distance_in_km' => 'decimal:2',
        ];
    }
}
