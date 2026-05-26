<?php

namespace App\Models;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int|null $employee_id
 * @property string $language_preference
 * @property bool $force_password_reset
 * @property bool $active
 * @property Carbon|null $last_login_at
 * @property int $login_attempts
 * @property Carbon|null $locked_until
 * @property Carbon|null $archived_at
 * @property int|null $archived_by
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class User extends Authenticatable implements Archivable
{
    use HasApiTokens;
    use HasArchive;
    use HasCompany;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use InteractsWithLog;
    use LogsActivity;
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
        'active',
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        $roleIds = $this->roles()->pluck('roles.id');

        return Permission::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roleIds))
            ->get();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()
            ->contains(fn (Permission $userPermission) => $userPermission->code === $permission);
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
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'archived_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
