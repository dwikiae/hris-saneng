<?php

namespace App\Models;

use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeOffboarding extends Model
{
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $table = 'employee_offboarding';

    protected $fillable = [
        'company_id',
        'employee_id',
        'termination_reason_id',
        'termination_date',
        'notes',
        'status',
        'finalized_by',
        'finalized_at',
        'created_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function terminationReason(): BelongsTo
    {
        return $this->belongsTo(TerminationReason::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EmployeeOffboardingItem::class);
    }

    protected function casts(): array
    {
        return [
            'termination_date' => 'date',
            'finalized_at' => 'datetime',
        ];
    }
}
