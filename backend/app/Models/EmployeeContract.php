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

class EmployeeContract extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    public const TYPE_PKWT = 'pkwt';

    public const TYPE_PKWTT = 'pkwtt';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_SUPERSEDED = 'superseded';

    public const STATUS_TERMINATED = 'terminated';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'contract_type',
        'contract_number',
        'start_date',
        'end_date',
        'status',
        'notes',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
