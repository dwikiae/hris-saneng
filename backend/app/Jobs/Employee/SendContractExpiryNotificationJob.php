<?php

namespace App\Jobs\Employee;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendContractExpiryNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly int $contractId,
        private readonly int $daysUntilExpiry
    ) {}

    public function handle(): void
    {
        Log::info('employee.contract_expiry_notification', [
            'contract_id' => $this->contractId,
            'days_until_expiry' => $this->daysUntilExpiry,
        ]);
    }
}
