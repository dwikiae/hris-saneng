<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeOffboarding;
use App\Repositories\Contracts\EmployeeOffboardingRepositoryInterface;

class EmployeeOffboardingRepository implements EmployeeOffboardingRepositoryInterface
{
    /**
     * @var list<string>
     */
    private array $relations = [
        'initiator',
        'completer',
        'checklistItems.assignee',
        'checklistItems.completer',
    ];

    public function activeForEmployee(Employee $employee): ?EmployeeOffboarding
    {
        /** @var EmployeeOffboarding|null $offboarding */
        $offboarding = $employee->offboardings()
            ->with($this->relations)
            ->where('status', '!=', EmployeeOffboarding::STATUS_COMPLETED)
            ->orderByDesc('initiated_at')
            ->orderByDesc('id')
            ->first();

        return $offboarding;
    }

    public function hasAnyHistoryForEmployee(Employee $employee): bool
    {
        return $employee->offboardings()
            ->withoutGlobalScope('not_archived')
            ->exists();
    }

    public function findForEmployee(Employee $employee, int $offboardingId): EmployeeOffboarding
    {
        /** @var EmployeeOffboarding $offboarding */
        $offboarding = $employee->offboardings()
            ->with($this->relations)
            ->findOrFail($offboardingId);

        return $offboarding;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeOffboarding
    {
        /** @var EmployeeOffboarding $offboarding */
        $offboarding = $employee->offboardings()->create(array_merge($data, [
            'company_id' => $employee->getAttribute('company_id'),
        ]));

        return $offboarding->load($this->relations);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeOffboarding $offboarding, array $data): EmployeeOffboarding
    {
        $offboarding->update($data);

        return $offboarding->refresh()->load($this->relations);
    }
}
