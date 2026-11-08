<?php

namespace App\Models;

use Database\Factories\SparePartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $price_in_cents
 * @property string $name
 * @property ?string $part_number
 * @property ?string $description
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property ?Carbon $deleted_at
 */
class SparePart extends Model
{
    /** @use HasFactory<SparePartFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'part_number', 'description', 'price_in_cents'];

    /** @return BelongsToMany<Work, $this> */
    public function works(): BelongsToMany
    {
        return $this->belongsToMany(Work::class)->withTimestamps();
    }

    public function formattedPrice(): string
    {
        return 'CZK ' . number_format(intdiv($this->price_in_cents, 100), 0, ',', ' ')
            . ',' . mb_str_pad((string) ($this->price_in_cents % 100), 2, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return ['price_in_cents' => 'integer'];
    }
}
