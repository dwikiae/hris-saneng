<?php

namespace App\Jobs\Employee;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendApprovalResultNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly int $employeeId,
        private readonly string $status
    ) {}

    public function handle(): void
    {
        Log::info('employee.approval_result', [
            'employee_id' => $this->employeeId,
            'status' => $this->status,
        ]);
    }
}
