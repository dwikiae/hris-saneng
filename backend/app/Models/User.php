<?php

namespace App\Models;

use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $locked_until
 */
class User extends Authenticatable
{
    use HasArchive;
    use HasCompany;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'employee_id',
        'language_preference',
        'force_password_reset',
        'last_login_at',
        'login_attempts',
        'locked_until',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
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
            'force_password_reset' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'archived_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
