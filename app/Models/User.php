<?php

namespace App\Models;

use App\Contracts\OwnsFiles;
use App\Traits\HasFiles;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string|null $address
 * @property string|null $remember_token
 * @property Carbon|null $email_verified_at
 */
class User extends Authenticatable implements OwnsFiles
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, TwoFactorAuthenticatable;

    use HasFiles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('users')
            ->logOnly(['name', 'email', 'phone', 'address', 'email_verified_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /** @return HasManyThrough<Quote, Issue, $this> */
    public function quotes(): HasManyThrough
    {
        return $this->hasManyThrough(Quote::class, Issue::class);
    }

    /** @return HasManyThrough<Work, Issue, $this> */
    public function works(): HasManyThrough
    {
        return $this->hasManyThrough(Work::class, Issue::class);
    }

    public function isFileOwner(self $user): bool
    {
        return $this->is($user);
    }

    /** @return HasMany<Issue, $this> */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
