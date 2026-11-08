<?php

namespace App\Models;

use App\Contracts\OwnsFiles;
use App\Enums\BoilerManufacturers;
use App\Enums\FileTypes;
use App\Enums\PdfGenerationStatus;
use App\Enums\WorkStatus;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $user_id
 * @property ?int $quote_id
 * @property string $order_id
 * @property ?PdfGenerationStatus $pdf_generation_status
 * @property ?string $pdf_generation_id
 * @property WorkStatus $status
 * @property ?string $type_of_product
 * @property ?BoilerManufacturers $boiler_manufacturer
 * @property ?string $type_number
 * @property ?string $serial_number
 * @property bool $warranty_accepted
 * @property ?string $repair_in_warranty
 * @property ?Carbon $day_of_purchase
 * @property ?string $building_type
 * @property ?string $reported_problem
 * @property ?Carbon $work_accepted_at
 * @property ?Carbon $work_started_at
 * @property ?Carbon $work_completed_at
 * @property ?string $work_performed_description
 * @property ?string $transport_from
 * @property ?string $transport_to
 * @property ?int $transport_rate_in_czk
 * @property ?float $transport_distance_in_km
 * @property ?string $notes
 * @property ?string $mechanic_name
 * @property ?Carbon $created_at
 * @property ?Carbon $deleted_at
 * @property ?Carbon $updated_at
 */
class Work extends Model implements OwnsFiles
{
    use HasFactory, LogsActivity, SoftDeletes;
    use HasFiles;

    protected $fillable = [
        'user_id',
        'quote_id',
        'order_id',
        'status',
        'type_of_product',
        'boiler_manufacturer',
        'type_number',
        'serial_number',
        'warranty_accepted',
        'repair_in_warranty',
        'day_of_purchase',
        'building_type',
        'reported_problem',
        'work_accepted_at',
        'work_started_at',
        'work_completed_at',
        'work_performed_description',
        'transport_from',
        'transport_to',
        'transport_rate_in_czk',
        'transport_distance_in_km',
        'notes',
        'mechanic_name',
    ];

    /** @return BelongsToMany<SparePart, $this> */
    public function spareParts(): BelongsToMany
    {
        return $this->belongsToMany(SparePart::class)->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('works')
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

    public function hasServiceProtocol(): bool
    {
        return $this->files()->where('file_type', FileTypes::SERVICE_PROTOCOL->value)->exists();
    }

    public function pdfGenerationInProgress(): bool
    {
        return in_array($this->pdf_generation_status, [PdfGenerationStatus::QUEUED, PdfGenerationStatus::PROCESSING], true);
    }

    public function start(): void
    {
        $this->status = WorkStatus::PROCESSING;
        $this->work_started_at = now();
        $this->save();
    }

    public function end(): void
    {
        $this->status = WorkStatus::FINISHED;
        $this->work_completed_at = now();
        $this->save();
    }

    public function cancel()
    {
        $this->status = WorkStatus::CANCELLED;
        $this->save();
    }

    public function isFileOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    protected function casts(): array
    {
        return [
            'status' => WorkStatus::class,
            'pdf_generation_status' => PdfGenerationStatus::class,
            'boiler_manufacturer' => BoilerManufacturers::class,
            'warranty_accepted' => 'boolean',
            'day_of_purchase' => 'date',
            'work_accepted_at' => 'date',
            'work_started_at' => 'date',
            'work_completed_at' => 'date',
            'transport_rate_in_czk' => 'integer',
            'transport_distance_in_km' => 'decimal:2',
        ];
    }
}
