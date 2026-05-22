<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Enums\Recruitment\ApplicantBlacklistStatus;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicantBlacklist extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'applicant_id',
        'status',
        'reason',
        'blacklisted_by',
        'blacklisted_at',
        'lifted_at',
        'lifted_reason',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function blacklistedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blacklisted_by');
    }

    protected function casts(): array
    {
        return [
            'status' => ApplicantBlacklistStatus::class,
            'blacklisted_at' => 'datetime',
            'lifted_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
