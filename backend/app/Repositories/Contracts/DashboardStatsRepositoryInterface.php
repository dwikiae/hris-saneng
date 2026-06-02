<?php

namespace App\Repositories\Contracts;

interface DashboardStatsRepositoryInterface
{
    public function totalEmployees(int $companyId): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingEmployeeApprovals(int $companyId, int $limit): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentEmployeeActivities(int $companyId, int $limit): array;
}
