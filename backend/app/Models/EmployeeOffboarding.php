<?php

namespace App\Models;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeOffboarding extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    public const REASON_RESIGNATION = 'resignation';

    public const REASON_TERMINATION = 'termination';

    public const REASON_CONTRACT_END = 'contract_end';

    public const REASON_RETIREMENT = 'retirement';

    public const REASON_OTHER = 'other';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'reason_type',
        'reason_detail',
        'last_working_date',
        'status',
        'initiated_by',
        'initiated_at',
        'completed_by',
        'completed_at',
        'notes',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(OffboardingChecklistItem::class, 'offboarding_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_working_date' => 'date',
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
