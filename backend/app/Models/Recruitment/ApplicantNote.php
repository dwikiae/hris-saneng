<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Enums\Recruitment\ApplicantStage;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicantNote extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'applicant_id',
        'user_id',
        'stage',
        'note',
        'is_private',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'stage' => ApplicantStage::class,
            'is_private' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
