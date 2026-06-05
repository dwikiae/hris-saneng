<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Repositories\Contracts\EmployeeEducationRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Collection;

class EmployeeEducationRepository implements EmployeeEducationRepositoryInterface
{
    public function __construct(private readonly ArchiveService $archiveService) {}

    /**
     * @return Collection<int, EmployeeEducation>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return $employee->education()
            ->with('educationLevel')
            ->orderByDesc('end_year')
            ->orderByDesc('start_year')
            ->orderBy('institution_name')
            ->get();
    }

    public function findForEmployee(Employee $employee, int $educationId): EmployeeEducation
    {
        /** @var EmployeeEducation $education */
        $education = $employee->education()
            ->with('educationLevel')
            ->findOrFail($educationId);

        return $education;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeEducation
    {
        /** @var EmployeeEducation $education */
        $education = $employee->education()->create(array_merge($data, [
            'company_id' => $employee->getAttribute('company_id'),
        ]));

        return $education->load('educationLevel');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeEducation $education, array $data): EmployeeEducation
    {
        $education->update($data);

        return $education->refresh()->load('educationLevel');
    }

    public function archive(EmployeeEducation $education): void
    {
        $this->archiveService->archive($education);
    }
}
