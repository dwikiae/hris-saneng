<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeExperience;
use App\Repositories\Contracts\EmployeeExperienceRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Collection;

class EmployeeExperienceRepository implements EmployeeExperienceRepositoryInterface
{
    public function __construct(private readonly ArchiveService $archiveService) {}

    /**
     * @return Collection<int, EmployeeExperience>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return $employee->experience()
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->orderBy('company_name')
            ->get();
    }

    public function findForEmployee(Employee $employee, int $experienceId): EmployeeExperience
    {
        /** @var EmployeeExperience $experience */
        $experience = $employee->experience()->findOrFail($experienceId);

        return $experience;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeExperience
    {
        /** @var EmployeeExperience $experience */
        $experience = $employee->experience()->create(array_merge($data, [
            'company_id' => $employee->getAttribute('company_id'),
        ]));

        return $experience;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeExperience $experience, array $data): EmployeeExperience
    {
        $experience->update($data);

        return $experience->refresh();
    }

    public function archive(EmployeeExperience $experience): void
    {
        $this->archiveService->archive($experience);
    }
}
