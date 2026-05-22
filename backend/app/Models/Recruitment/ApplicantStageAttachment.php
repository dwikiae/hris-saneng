<?php

namespace App\Models\Recruitment;

use App\Enums\Recruitment\ApplicantStage;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicantStageAttachment extends Model
{
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'applicant_id',
        'stage',
        'original_filename',
        'storage_disk',
        'path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'created_by',
        'updated_by',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    protected function casts(): array
    {
        return [
            'stage' => ApplicantStage::class,
        ];
    }
}
