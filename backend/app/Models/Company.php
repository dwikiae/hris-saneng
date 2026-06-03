<?php

namespace App\Models;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class Company extends Model implements Archivable
{
    use HasArchive;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

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
        'archived_at',
        'archived_by',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }
}
