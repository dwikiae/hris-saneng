<?php

namespace App\Application\Dashboard;

use App\Models\User;
use App\Repositories\Contracts\DashboardStatsRepositoryInterface;

class DashboardStatsService
{
    private const DASHBOARD_LIST_LIMIT = 5;

    public function __construct(private readonly DashboardStatsRepositoryInterface $dashboardStats) {}

    /**
     * @return array<string, mixed>
     */
    public function stats(int $companyId, User $user): array
    {
        return [
            'stats' => [
                'total_employees' => $this->dashboardStats->totalEmployees($companyId),
                'present_today' => 0,
                'absent_today' => 0,
                'leave_today' => 0,
            ],
            'pending_approvals' => $user->hasPermission('employee.approve')
                ? $this->dashboardStats->pendingEmployeeApprovals($companyId, self::DASHBOARD_LIST_LIMIT)
                : [],
            'recent_activities' => $this->dashboardStats->recentEmployeeActivities($companyId, self::DASHBOARD_LIST_LIMIT),
        ];
    }
}
