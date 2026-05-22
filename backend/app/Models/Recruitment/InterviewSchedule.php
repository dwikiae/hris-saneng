<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Enums\Recruitment\InterviewScheduleStatus;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class InterviewSchedule extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'applicant_id',
        'job_posting_id',
        'interviewer_id',
        'token',
        'expires_at',
        'scheduled_at',
        'duration_minutes',
        'location',
        'confirmation_status',
        'confirmed_at',
        'status',
        'timer_snapshot',
        'passing_grade_snapshot',
        'score',
        'result_notes',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'status' => InterviewScheduleStatus::class,
            'passing_grade_snapshot' => 'decimal:2',
            'score' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }
}
