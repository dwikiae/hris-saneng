<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Enums\Recruitment\ApplicantSource;
use App\Enums\Recruitment\ApplicantStage;
use App\Enums\Recruitment\ApplicantStatus;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;

class Applicant extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'job_posting_id',
        'application_number',
        'name',
        'email',
        'phone',
        'address',
        'birth_date',
        'birth_place',
        'gender',
        'nik',
        'source',
        'stage',
        'status',
        'is_duplicate',
        'is_blacklisted',
        'rejection_reason',
        'consent_at',
        'approved_by',
        'approved_at',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(ApplicantEducation::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(ApplicantExperience::class);
    }

    public function quizSessions(): HasMany
    {
        return $this->hasMany(QuizSession::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicantNote::class);
    }

    public function stageAttachments(): HasMany
    {
        return $this->hasMany(ApplicantStageAttachment::class);
    }

    public function blacklist(): HasOne
    {
        return $this->hasOne(ApplicantBlacklist::class)->latestOfMany();
    }

    public function interviewSchedules(): HasMany
    {
        return $this->hasMany(InterviewSchedule::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    protected function sensitiveActivityLogAttributes(): array
    {
        return ['nik'];
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'nik' => 'encrypted',
            'source' => ApplicantSource::class,
            'stage' => ApplicantStage::class,
            'status' => ApplicantStatus::class,
            'is_duplicate' => 'boolean',
            'is_blacklisted' => 'boolean',
            'consent_at' => 'datetime',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
