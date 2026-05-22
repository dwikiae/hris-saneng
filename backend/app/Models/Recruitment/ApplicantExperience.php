<?php

namespace App\Models\Recruitment;

use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class ApplicantExperience extends Model
{
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $table = 'applicant_experiences';

    protected $fillable = [
        'company_id',
        'applicant_id',
        'company_name',
        'position',
        'start_date',
        'end_date',
        'description',
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
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
