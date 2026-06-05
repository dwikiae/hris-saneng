<?php

namespace App\Services\Employee;

use App\Models\EmployeeExperience;
use App\Repositories\Contracts\EmployeeExperienceRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class EmployeeExperienceService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeExperienceRepositoryInterface $experience
    ) {}

    /**
     * @return Collection<int, EmployeeExperience>
     */
    public function list(int $employeeId): Collection
    {
        $employee = $this->employees->show($employeeId);

        return $this->experience->listForEmployee($employee);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(int $employeeId, array $data): EmployeeExperience
    {
        $employee = $this->employees->show($employeeId);
        $payload = $this->normalizeCurrentExperience($data);

        return $this->experience->createForEmployee($employee, array_merge($payload, [
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $employeeId, int $experienceId, array $data): EmployeeExperience
    {
        $employee = $this->employees->show($employeeId);
        $experience = $this->experience->findForEmployee($employee, $experienceId);
        $payload = $this->normalizeCurrentExperience($data, (bool) $experience->getAttribute('is_current'));

        return $this->experience->update($experience, array_merge($payload, [
            'updated_by' => Auth::id(),
        ]));
    }

    public function archive(int $employeeId, int $experienceId): void
    {
        $employee = $this->employees->show($employeeId);
        $experience = $this->experience->findForEmployee($employee, $experienceId);

        $this->experience->archive($experience);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCurrentExperience(array $data, bool $currentIsCurrent = false): array
    {
        $isCurrent = array_key_exists('is_current', $data)
            ? filter_var($data['is_current'], FILTER_VALIDATE_BOOLEAN)
            : $currentIsCurrent;

        if ($isCurrent && array_key_exists('end_date', $data) && $data['end_date'] !== null && $data['end_date'] !== '') {
            throw new InvalidArgumentException('employee.experience.current_end_date_conflict');
        }

        if ($isCurrent) {
            $data['end_date'] = null;
            $data['is_current'] = true;
        } else {
            $data['is_current'] = false;
        }

        return $data;
    }
}
