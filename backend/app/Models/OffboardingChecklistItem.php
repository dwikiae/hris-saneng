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

class OffboardingChecklistItem extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'offboarding_id',
        'title',
        'description',
        'assigned_to',
        'is_completed',
        'completed_by',
        'completed_at',
        'due_date',
        'order',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function offboarding(): BelongsTo
    {
        return $this->belongsTo(EmployeeOffboarding::class, 'offboarding_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'due_date' => 'date',
            'order' => 'integer',
            'archived_at' => 'datetime',
        ];
    }
}
