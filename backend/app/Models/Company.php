<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'slug',
        'npwp',
        'address',
        'city',
        'phone',
        'email',
        'logo_path',
        'website',
        'timezone',
        'date_format',
        'language_default',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
