<?php

namespace App\Models;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use App\Modules\Karyawan\Models\City;
use App\Modules\Karyawan\Models\EmployeeLevel;
use App\Modules\Karyawan\Models\Province;
use App\Modules\Karyawan\Models\WorkLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    public const DRAFT = 'draft';

    public const PENDING = 'pending';

    public const ACTIVE = 'active';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_number',
        'name',
        'nickname',
        'email',
        'phone',
        'address',
        'province_id',
        'city_id',
        'domicile_address',
        'domicile_province_id',
        'domicile_city_id',
        'birth_date',
        'birth_place',
        'country_of_birth',
        'gender',
        'religion_id',
        'marital_status_id',
        'blood_type_id',
        'nationality',
        'passport_number',
        'department_id',
        'position_id',
        'employment_type_id',
        'employee_level_id',
        'work_location_id',
        'supervisor_id',
        'join_date',
        'probation_end_date',
        'end_date',
        'nik',
        'npwp',
        'bank_name',
        'bank_account_number',
        'salary',
        'allowances',
        'deductions',
        'consent_at',
        'consent_by',
        'status',
        'approver_id',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function canTransitionTo(string $status): bool
    {
        $currentStatus = $this->getAttribute('status');

        if (! is_string($currentStatus)) {
            return false;
        }

        return in_array($status, $this->allowedTransitions()[$currentStatus] ?? [], true);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function family(): HasMany
    {
        return $this->hasMany(EmployeeFamily::class);
    }

    public function photo(): HasOne
    {
        return $this->hasOne(EmployeePhoto::class)->latestOfMany();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function bloodType(): BelongsTo
    {
        return $this->belongsTo(BloodType::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id', 'code');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id', 'code');
    }

    public function domicileProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'domicile_province_id', 'code');
    }

    public function domicileCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'domicile_city_id', 'code');
    }

    public function employeeLevel(): BelongsTo
    {
        return $this->belongsTo(EmployeeLevel::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    /**
     * @return array<int, string>
     */
    protected function sensitiveActivityLogAttributes(): array
    {
        return [
            'nik',
            'npwp',
            'passport_number',
            'bank_account_number',
            'salary',
            'allowances',
            'deductions',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function allowedTransitions(): array
    {
        return [
            self::DRAFT => [self::PENDING],
            self::PENDING => [self::APPROVED, self::REJECTED],
            self::REJECTED => [self::PENDING],
            self::APPROVED => [self::ACTIVE],
            self::ACTIVE => [self::PENDING],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'join_date' => 'date',
            'probation_end_date' => 'date',
            'end_date' => 'date',
            'nik' => 'encrypted',
            'npwp' => 'encrypted',
            'passport_number' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'salary' => 'encrypted',
            'allowances' => 'encrypted',
            'deductions' => 'encrypted',
            'consent_at' => 'datetime',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
