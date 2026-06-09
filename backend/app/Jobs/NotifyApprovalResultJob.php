<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyApprovalResultJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly int $employeeId,
        private readonly string $status
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(): void
    {
        Log::info('employee.approval_result', [
            'employee_id' => $this->employeeId,
            'status' => $this->status,
        ]);
    }
}
