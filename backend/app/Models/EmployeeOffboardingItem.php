<?php

namespace App\Models;

use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeOffboardingItem extends Model
{
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'employee_offboarding_id',
        'offboarding_template_item_id',
        'item_name',
        'department_responsible',
        'is_mandatory',
        'sort_order',
        'is_done',
        'done_by',
        'done_at',
        'notes',
    ];

    public function offboarding(): BelongsTo
    {
        return $this->belongsTo(EmployeeOffboarding::class, 'employee_offboarding_id');
    }

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'is_done' => 'boolean',
            'sort_order' => 'integer',
            'done_at' => 'datetime',
        ];
    }
}
