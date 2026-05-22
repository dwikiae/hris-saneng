<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Enums\Recruitment\ApplicantDocumentType;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicantDocument extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'applicant_id',
        'document_type',
        'token',
        'expires_at',
        'original_filename',
        'storage_disk',
        'path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'uploaded_at',
        'archived_at',
        'archived_by',
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
            'document_type' => ApplicantDocumentType::class,
            'expires_at' => 'datetime',
            'uploaded_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
