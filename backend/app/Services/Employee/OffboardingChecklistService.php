<?php

namespace App\Services\Employee;

use App\Models\OffboardingChecklistItem;
use App\Repositories\Contracts\EmployeeOffboardingRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\OffboardingChecklistRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class OffboardingChecklistService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeOffboardingRepositoryInterface $offboardings,
        private readonly OffboardingChecklistRepositoryInterface $checklist
    ) {}

    /**
     * @return Collection<int, OffboardingChecklistItem>
     */
    public function list(int $employeeId, int $offboardingId): Collection
    {
        $offboarding = $this->offboardingForEmployee($employeeId, $offboardingId);

        return $this->checklist->listForOffboarding($offboarding);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(int $employeeId, int $offboardingId, array $data): OffboardingChecklistItem
    {
        $offboarding = $this->offboardingForEmployee($employeeId, $offboardingId);

        return $this->checklist->createForOffboarding($offboarding, array_merge($data, [
            'is_completed' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $employeeId, int $offboardingId, int $itemId, array $data): OffboardingChecklistItem
    {
        $offboarding = $this->offboardingForEmployee($employeeId, $offboardingId);
        $item = $this->checklist->findForOffboarding($offboarding, $itemId);

        return $this->checklist->update($item, array_merge($data, [
            'updated_by' => Auth::id(),
        ]));
    }

    public function complete(int $employeeId, int $offboardingId, int $itemId): OffboardingChecklistItem
    {
        $offboarding = $this->offboardingForEmployee($employeeId, $offboardingId);
        $item = $this->checklist->findForOffboarding($offboarding, $itemId);

        return $this->checklist->update($item, [
            'is_completed' => true,
            'completed_by' => Auth::id(),
            'completed_at' => now(),
            'updated_by' => Auth::id(),
        ]);
    }

    public function archive(int $employeeId, int $offboardingId, int $itemId): void
    {
        $offboarding = $this->offboardingForEmployee($employeeId, $offboardingId);
        $item = $this->checklist->findForOffboarding($offboarding, $itemId);

        $this->checklist->archive($item);
    }

    private function offboardingForEmployee(int $employeeId, int $offboardingId)
    {
        $employee = $this->employees->show($employeeId);

        return $this->offboardings->findForEmployee($employee, $offboardingId);
    }
}
