<?php

namespace App\Models;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    public const DRAFT = 'draft';
    public const ACTIVE = 'active';
    public const EXPIRED = 'expired';
    public const TERMINATED = 'terminated';

    protected $fillable = [
        'company_id',
        'employee_id',
        'contract_number',
        'contract_type_id',
        'position_id',
        'department_id',
        'work_location_id',
        'start_date',
        'end_date',
        'status',
        'termination_reason',
        'termination_date',
        'termination_notes',
        'document_path',
        'signed_date',
        'approved_by',
        'approved_at',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'termination_date' => 'date',
            'signed_date' => 'date',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
