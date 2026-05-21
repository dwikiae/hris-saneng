<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'resource',
        'field',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
