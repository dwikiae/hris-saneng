<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Enums\Recruitment\JobPostingStatus;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class JobPosting extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'department_id',
        'position_id',
        'test_id',
        'code',
        'title',
        'description',
        'requirements',
        'employment_type',
        'location',
        'quota',
        'status',
        'published_at',
        'expired_at',
        'closed_at',
        'approved_by',
        'approved_at',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(JobPostingCriterion::class);
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    public function interviewSchedules(): HasMany
    {
        return $this->hasMany(InterviewSchedule::class);
    }

    protected function casts(): array
    {
        return [
            'status' => JobPostingStatus::class,
            'published_at' => 'datetime',
            'expired_at' => 'datetime',
            'closed_at' => 'datetime',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
