<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeFamily;
use App\Repositories\Contracts\EmployeeFamilyRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Collection;

class EmployeeFamilyRepository implements EmployeeFamilyRepositoryInterface
{
    public function __construct(private readonly ArchiveService $archiveService) {}

    /**
     * @return Collection<int, EmployeeFamily>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return $employee->family()
            ->orderBy('relationship')
            ->orderBy('name')
            ->get();
    }

    public function findForEmployee(Employee $employee, int $familyId): EmployeeFamily
    {
        /** @var EmployeeFamily $family */
        $family = $employee->family()->findOrFail($familyId);

        return $family;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeFamily
    {
        /** @var EmployeeFamily $family */
        $family = $employee->family()->create(array_merge($data, [
            'company_id' => $employee->getAttribute('company_id'),
        ]));

        return $family;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeFamily $family, array $data): EmployeeFamily
    {
        $family->update($data);

        return $family->refresh();
    }

    public function archive(EmployeeFamily $family): void
    {
        $this->archiveService->archive($family);
    }
}
