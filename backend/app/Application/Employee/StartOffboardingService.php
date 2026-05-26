<?php

namespace App\Application\Employee;

use App\Domain\Employee\EmployeeStatus;
use App\Models\Employee;
use App\Models\EmployeeOffboarding;
use App\Repositories\Contracts\EmployeeOffboardingRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StartOffboardingService
{
    public function __construct(
        private readonly EmployeeOffboardingRepositoryInterface $offboarding,
        private readonly EmployeeRepositoryInterface $employees
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(Employee $employee, array $data): EmployeeOffboarding
    {
        Gate::authorize('offboarding.manage');

        if ($employee->getAttribute('status') !== EmployeeStatus::Active->value) {
            throw ValidationException::withMessages(['status' => ['employee.error.offboard_not_active']]);
        }

        return DB::transaction(function () use ($employee, $data): EmployeeOffboarding {
            $companyId = (int) $employee->getAttribute('company_id');
            $template = $this->offboarding->findTemplateByReason($companyId, (int) $data['termination_reason_id']);
            $record = $this->offboarding->create([
                'company_id' => $companyId,
                'employee_id' => $employee->getKey(),
                'termination_reason_id' => $data['termination_reason_id'],
                'termination_date' => $data['termination_date'],
                'notes' => $data['notes'] ?? null,
                'status' => 'in_progress',
                'created_by' => Auth::id(),
            ]);

            if ($template !== null) {
                foreach ($template->getRelation('items') as $item) {
                    $this->offboarding->createItem($record, [
                        'company_id' => $companyId,
                        'offboarding_template_item_id' => $item->getKey(),
                        'item_name' => $item->getAttribute('item_name'),
                        'department_responsible' => $item->getAttribute('department_responsible'),
                        'is_mandatory' => $item->getAttribute('is_mandatory'),
                        'sort_order' => $item->getAttribute('sort_order'),
                    ]);
                }
            }

            $this->employees->forceUpdate($employee, [
                'offboarding_started_at' => now(),
                'offboarding_started_by' => Auth::id(),
                'termination_reason_id' => $data['termination_reason_id'],
                'termination_notes' => $data['notes'] ?? null,
            ]);
            $this->employees->createSystemLog($employee, 'Proses offboarding dimulai');

            return $record->refresh()->load('items');
        });
    }
}
