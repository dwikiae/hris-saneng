<?php

namespace App\Application\Employee;

use App\Domain\Employee\EmployeeStatus;
use App\Domain\Employee\Rules\ConsentRequiredRule;
use App\Domain\Employee\Rules\NikFormatRule;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateEmployeeService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly NikFormatRule $nikFormatRule,
        private readonly ConsentRequiredRule $consentRequiredRule
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): Employee
    {
        Gate::authorize('employee.create');
        $this->validatePersonalData($data);

        $fullName = (string) ($data['full_name'] ?? $data['name'] ?? '');
        $actorId = Auth::id();

        $employee = $this->employees->create(array_merge($data, [
            'company_id' => (int) ($data['company_id'] ?? config('app.company_id')),
            'employee_number' => null,
            'full_name' => $fullName,
            'name' => $fullName,
            'created_by' => $actorId,
            'updated_by' => $actorId,
            'consent_by' => $actorId,
            'status' => EmployeeStatus::Pending->value,
        ]));

        $this->employees->createSystemLog($employee, 'Data karyawan dibuat oleh '.$this->actorName());

        return $employee;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function validatePersonalData(array $data): void
    {
        try {
            $this->nikFormatRule->assert((string) ($data['nik'] ?? ''));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['nik' => [$exception->getMessage()]]);
        }

        try {
            $this->consentRequiredRule->assert($data['consent_at'] ?? null, $data['consent_text'] ?? null);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['consent_at' => [$exception->getMessage()]]);
        }
    }

    private function actorName(): string
    {
        $user = Auth::user();

        return $user instanceof User ? $user->name : 'System';
    }
}
