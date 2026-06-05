<?php

namespace App\Models;

use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class EmployeeNote extends Model
{
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    public const TYPE_MANUAL = 'manual';

    public const TYPE_SYSTEM = 'system';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'content',
        'type',
        'mentioned_users',
        'created_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mentioned_users' => 'array',
        ];
    }
}
