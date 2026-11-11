<?php

namespace App\Models;

use App\Contracts\OwnsFiles;
use App\Enums\BoilerManufacturers;
use App\Enums\FileTypes;
use App\Enums\PdfGenerationStatus;
use App\Enums\QuoteStatus;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $issue_id
 * @property int $id
 * @property QuoteStatus $status
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property ?string $address
 * @property string $message
 * @property bool $under_warranty
 * @property ?BoilerManufacturers $boiler_manufacturer
 * @property ?string $boiler_serial_number
 * @property ?string $boiler_type
 * @property ?PdfGenerationStatus $pdf_generation_status
 * @property ?string $pdf_generation_id
 * @property ?Carbon $created_at
 * @property ?Carbon $deleted_at
 * @property ?Carbon $updated_at
 */
class Quote extends Model implements OwnsFiles
{
    use HasFactory, LogsActivity, SoftDeletes;
    use HasFiles;

    protected $fillable = [
        'issue_id',
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
            ->useLogName('quotes')
            ->logAll()
            ->logOnlyDirty();
    }

    /** @return HasOneThrough<User, Issue, $this> */
    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Issue::class, 'id', 'id', 'issue_id', 'user_id')->withTrashedParents();
    }

    public function isClosed(): bool
    {
        return $this->status === QuoteStatus::ACCEPTED
            or $this->status === QuoteStatus::REJECTED;
    }

    public function hasQuoteSummary(): bool
    {
        return $this->files()
            ->where('file_type', FileTypes::QUOTE_SUMMARY->value)
            ->exists();
    }

    public function pdfGenerationInProgress(): bool
    {
        return in_array($this->pdf_generation_status, [PdfGenerationStatus::QUEUED, PdfGenerationStatus::PROCESSING], true);
    }

    /** @return HasOne<Work, $this> */
    public function work(): HasOne
    {
        return $this->hasOne(Work::class, 'quote_id');
    }

    public function isFileOwner(User $user): bool
    {
        return $this->issue()->withTrashed()->where('user_id', $user->id)->exists();
    }

    /** @return BelongsTo<Issue, $this> */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class)->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'under_warranty' => 'boolean',
            'status' => QuoteStatus::class,
            'pdf_generation_status' => PdfGenerationStatus::class,
            'boiler_manufacturer' => BoilerManufacturers::class,
        ];
    }
}
