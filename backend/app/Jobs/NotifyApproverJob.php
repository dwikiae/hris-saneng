<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyApproverJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly int $employeeId) {}

    public function handle(): void
    {
        Log::info('employee.approval_requested', [
            'employee_id' => $this->employeeId,
        ]);
    }
}
