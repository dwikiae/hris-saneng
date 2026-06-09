<?php

namespace App\Modules\Karyawan\Models;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class ContractType extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    public const TYPE_PKWT = 'pkwt';

    public const TYPE_PKWTT = 'pkwtt';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'description',
        'max_duration_months',
        'is_active',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'max_duration_months' => 'integer',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];
}
