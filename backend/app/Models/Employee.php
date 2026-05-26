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

    public const ARCHIVED = 'archived';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'employee_number',
        'full_name',
        'nickname',
        'name',
        'email',
        'phone',
        'address',
        'birth_date',
        'birth_place',
        'gender',
        'place_of_birth',
        'date_of_birth',
        'religion',
        'blood_type',
        'marital_status',
        'nationality',
        'number_of_children',
        'spouse_name',
        'spouse_date_of_birth',
        'department_id',
        'division_id',
        'unit_id',
        'position_id',
        'job_level_id',
        'employment_type_id',
        'work_location_id',
        'join_date',
        'first_contract_date',
        'end_date',
        'termination_reason_id',
        'termination_notes',
        'offboarding_started_at',
        'offboarding_started_by',
        'work_email',
        'work_phone',
        'work_mobile',
        'address_ktp',
        'address_domisili',
        'private_phone',
        'private_email',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'bpjs_kesehatan_number',
        'bpjs_ketenagakerjaan_number',
        'nik',
        'npwp',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'work_permit_number',
        'work_permit_expiry',
        'barcode',
        'pin',
        'consent_at',
        'consent_by',
        'consent_text',
        'status',
        'approver_id',
        'user_id',
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

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(EmployeeExperience::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmployeeFamilyMember::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function offboardingRecords(): HasMany
    {
        return $this->hasMany(EmployeeOffboarding::class);
    }

    public function chatterMessages(): HasMany
    {
        return $this->hasMany(EmployeeChatterMessage::class);
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

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    public function terminationReason(): BelongsTo
    {
        return $this->belongsTo(TerminationReason::class);
    }

    /**
     * @return array<int, string>
     */
    protected function sensitiveActivityLogAttributes(): array
    {
        return [
            'nik',
            'npwp',
            'bank_account_number',
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
            'date_of_birth' => 'date',
            'spouse_date_of_birth' => 'date',
            'join_date' => 'date',
            'first_contract_date' => 'date',
            'end_date' => 'date',
            'offboarding_started_at' => 'datetime',
            'nik' => 'encrypted',
            'npwp' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'work_permit_expiry' => 'date',
            'number_of_children' => 'integer',
            'consent_at' => 'datetime',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }
}
