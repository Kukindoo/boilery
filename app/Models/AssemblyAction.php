<?php

namespace App\Models;

use Database\Factories\AssemblyActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read Pivot $pivot
 * @property int $id
 * @property string $code
 * @property string $name
 * @property ?string $description
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property ?Carbon $deleted_at
 */
class AssemblyAction extends Model
{
    /** @use HasFactory<AssemblyActionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'name', 'description'];

    /** @return BelongsToMany<Work, $this> */
    public function works(): BelongsToMany
    {
        return $this->belongsToMany(Work::class)->withPivot('price_in_cents')->withTimestamps();
    }
}
