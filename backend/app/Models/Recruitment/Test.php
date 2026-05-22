<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class Test extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'duration_minutes',
        'passing_grade',
        'is_active',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(TestQuestion::class);
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    protected function casts(): array
    {
        return [
            'passing_grade' => 'decimal:2',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
