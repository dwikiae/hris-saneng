<?php

namespace App\Models;

use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;
    use HasArchive;
    use HasCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    public function fieldPermissions(): HasMany
    {
        return $this->hasMany(FieldPermission::class);
    }
}
