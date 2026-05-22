<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Enums\Recruitment\QuizSessionStatus;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class QuizSession extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'applicant_id',
        'test_id',
        'token',
        'status',
        'timer_snapshot',
        'passing_grade_snapshot',
        'started_at',
        'submitted_at',
        'finished_at',
        'expires_at',
        'score',
        'nilai_akhir',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    protected function casts(): array
    {
        return [
            'status' => QuizSessionStatus::class,
            'passing_grade_snapshot' => 'decimal:2',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
            'score' => 'decimal:2',
            'nilai_akhir' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }
}
