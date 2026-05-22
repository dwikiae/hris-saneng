<?php

namespace App\Models\Recruitment;

use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicantEducation extends Model
{
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $table = 'applicant_educations';

    protected $fillable = [
        'company_id',
        'applicant_id',
        'institution_name',
        'degree',
        'major',
        'graduation_year',
        'gpa',
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
            'gpa' => 'decimal:2',
        ];
    }
}
