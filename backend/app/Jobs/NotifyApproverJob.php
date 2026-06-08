<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyApproverJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly int $employeeId) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(): void
    {
        /** @var Employee|null $employee */
        $employee = Employee::withoutCompanyScope()
            ->withoutGlobalScope('not_archived')
            ->find($this->employeeId);

        if (! $employee instanceof Employee) {
            return;
        }

        $this->approvers((int) $employee->getAttribute('company_id'))
            ->each(fn (User $user): Notification => Notification::withoutCompanyScope()->create([
                'company_id' => (int) $employee->getAttribute('company_id'),
                'user_id' => (int) $user->getKey(),
                'type' => 'employee.approval_requested',
                'title_key' => 'employee.notifications.approval_requested.title',
                'body_key' => 'employee.notifications.approval_requested.body',
                'data' => [
                    'employee_id' => $employee->getKey(),
                    'employee_number' => $employee->getAttribute('employee_number'),
                    'employee_name' => $employee->getAttribute('name'),
                ],
                'created_by' => $employee->getAttribute('updated_by') ?? $employee->getAttribute('created_by'),
            ]));

        Log::info('employee.approval_requested', [
            'employee_id' => $this->employeeId,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function approvers(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return User::withoutCompanyScope()
            ->whereHas('roles', function ($query) use ($companyId): void {
                $query->withoutGlobalScope('company')
                    ->where('roles.company_id', $companyId)
                    ->whereHas('permissions', fn ($permissionQuery) => $permissionQuery
                        ->withoutGlobalScope('company')
                        ->where('permissions.company_id', $companyId)
                        ->where('permissions.code', 'employee.approve'));
            })
            ->orderBy('id')
            ->get();
    }
}
