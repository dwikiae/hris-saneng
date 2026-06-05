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

class EmployeeFamily extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    public const RELATIONSHIP_SPOUSE = 'spouse';

    public const RELATIONSHIP_CHILD = 'child';

    public const RELATIONSHIP_PARENT = 'parent';

    public const RELATIONSHIP_SIBLING = 'sibling';

    public const RELATIONSHIP_OTHER = 'other';

    protected $table = 'employee_family';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_id',
        'name',
        'relationship',
        'birth_date',
        'gender',
        'occupation',
        'phone',
        'is_dependent',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_dependent' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
