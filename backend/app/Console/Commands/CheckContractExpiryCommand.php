<?php

namespace App\Console\Commands;

use App\Jobs\Employee\SendContractExpiryNotificationJob;
use App\Repositories\Contracts\ContractRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CheckContractExpiryCommand extends Command
{
    protected $signature = 'employee:check-contract-expiry';

    protected $description = 'Queue employee contract expiry notifications for H-60, H-30, and H-7.';

    /**
     * @var list<int>
     */
    private array $notificationDays = [60, 30, 7];

    public function handle(ContractRepositoryInterface $contracts): int
    {
        $today = CarbonImmutable::today();
        $dates = array_map(
            fn (int $days): string => $today->addDays($days)->toDateString(),
            $this->notificationDays
        );

        foreach ($contracts->listActiveExpiringOnDates($dates) as $contract) {
            $daysUntilExpiry = $today->diffInDays(CarbonImmutable::parse($contract->getAttribute('end_date')));
            SendContractExpiryNotificationJob::dispatch((int) $contract->getKey(), (int) $daysUntilExpiry);
        }

        $this->info('employee.contract_expiry_check_queued');

        return self::SUCCESS;
    }
}
