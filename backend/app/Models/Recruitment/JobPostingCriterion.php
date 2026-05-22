<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class JobPostingCriterion extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'job_posting_id',
        'code',
        'name',
        'description',
        'bobot',
        'is_required',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    protected function casts(): array
    {
        return [
            'bobot' => 'decimal:2',
            'is_required' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
