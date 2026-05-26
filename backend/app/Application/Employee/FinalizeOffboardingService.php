<?php

namespace App\Application\Employee;

use App\Domain\Employee\EmployeeStatus;
use App\Domain\Employee\Exceptions\IncompleteChecklistException;
use App\Models\Employee;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\EmployeeOffboardingRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FinalizeOffboardingService
{
    public function __construct(
        private readonly EmployeeOffboardingRepositoryInterface $offboarding,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly ContractRepositoryInterface $contracts
    ) {}

    /**
     * @throws IncompleteChecklistException
     */
    public function execute(Employee $employee, int $offboardingId): void
    {
        Gate::authorize('offboarding.manage');
        $record = $this->offboarding->findById($offboardingId);

        if ($this->offboarding->hasIncompleteMandatoryItems($record)) {
            throw new IncompleteChecklistException('employee.error.finalize_incomplete');
        }

        DB::transaction(function () use ($employee, $record): void {
            $userId = (int) Auth::id();
            $terminationDate = CarbonImmutable::parse($record->getAttribute('termination_date'))->toDateString();

            $this->employees->forceUpdate($employee, [
                'end_date' => $terminationDate,
                'archived_at' => now(),
                'archived_by' => $userId,
                'status' => EmployeeStatus::Archived->value,
            ]);
            $this->contracts->terminateActiveByEmployee((int) $employee->getKey(), $terminationDate, $userId);
            $this->employees->deactivateLinkedUser($employee);
            $this->offboarding->update($record, [
                'status' => 'completed',
                'finalized_by' => $userId,
                'finalized_at' => now(),
            ]);
            $this->employees->createSystemLog(
                $employee,
                'Offboarding difinalisasi. Tanggal berakhir: '.$terminationDate.'. Semua kontrak aktif diterminasi. Akun user dinonaktifkan.'
            );
        });
    }
}
