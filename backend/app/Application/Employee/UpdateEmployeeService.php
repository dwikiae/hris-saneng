<?php

namespace App\Application\Employee;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UpdateEmployeeService
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_FIELDS = [
        'nik',
        'npwp',
        'bank_account_number',
    ];

    public function __construct(private readonly EmployeeRepositoryInterface $employees) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Employee $employee, array $data): Employee
    {
        Gate::authorize('employee.update');

        $changedFields = $this->changedFields($employee, $data);
        $updated = $this->employees->update($employee, array_merge($data, [
            'updated_by' => Auth::id(),
        ]));

        foreach ($changedFields as $field) {
            $this->employees->createSystemLog($updated, $this->changeMessage($field));
        }

        return $updated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function changedFields(Employee $employee, array $data): array
    {
        $fields = [];

        foreach ($data as $field => $value) {
            if ($employee->getAttribute($field) !== $value) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    private function changeMessage(string $field): string
    {
        $message = 'Field '.$field.' diperbarui oleh '.$this->actorName();

        if (in_array($field, self::SENSITIVE_FIELDS, true)) {
            return $message.' [REDACTED]';
        }

        return $message;
    }

    private function actorName(): string
    {
        $user = Auth::user();

        return $user instanceof User ? $user->name : 'System';
    }
}
