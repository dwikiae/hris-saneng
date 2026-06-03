<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\Contracts\DashboardStatsRepositoryInterface;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class DashboardStatsRepository implements DashboardStatsRepositoryInterface
{
    public function totalEmployees(int $companyId): int
    {
        return Employee::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingEmployeeApprovals(int $companyId, int $limit): array
    {
        return Employee::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', Employee::PENDING)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'employee_number', 'name', 'created_by', 'updated_by', 'created_at'])
            ->map(function (Employee $employee): array {
                return [
                    'id' => 'employee-'.$employee->getKey(),
                    'type' => 'employee',
                    'resource_id' => (int) $employee->getKey(),
                    'title' => $employee->getAttribute('name'),
                    'description' => $employee->getAttribute('employee_number'),
                    'requested_by' => $this->actorName($employee),
                    'created_at' => $employee->getAttribute('created_at'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentEmployeeActivities(int $companyId, int $limit): array
    {
        /** @var Collection<int, int> $employeeIds */
        $employeeIds = Employee::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->pluck('id');

        if ($employeeIds->isEmpty()) {
            return [];
        }

        /** @var Collection<int, Activity> $activities */
        $activities = Activity::query()
            ->where('subject_type', Employee::class)
            ->whereIn('subject_id', $employeeIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'log_name', 'description', 'causer_type', 'causer_id', 'created_at']);

        $actorNames = $this->actorNames($activities);

        return $activities
            ->map(fn (Activity $activity): array => [
                'id' => (int) $activity->getKey(),
                'title' => (string) $activity->getAttribute('description'),
                'description' => (string) ($activity->getAttribute('log_name') ?? 'employees'),
                'actor' => $actorNames[(int) $activity->getAttribute('causer_id')] ?? null,
                'module' => (string) ($activity->getAttribute('log_name') ?? 'employees'),
                'created_at' => $activity->getAttribute('created_at'),
            ])
            ->values()
            ->all();
    }

    private function actorName(Employee $employee): ?string
    {
        $actorId = $employee->getAttribute('updated_by') ?? $employee->getAttribute('created_by');

        if ($actorId === null) {
            return null;
        }

        return User::query()
            ->withoutGlobalScope('company')
            ->whereKey((int) $actorId)
            ->value('name');
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<int, string>
     */
    private function actorNames(Collection $activities): array
    {
        $actorIds = $activities
            ->filter(fn (Activity $activity): bool => $activity->getAttribute('causer_type') === User::class)
            ->pluck('causer_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($actorIds->isEmpty()) {
            return [];
        }

        /** @var array<int, string> $names */
        $names = User::query()
            ->withoutGlobalScope('company')
            ->whereIn('id', $actorIds)
            ->pluck('name', 'id')
            ->all();

        return $names;
    }
}
