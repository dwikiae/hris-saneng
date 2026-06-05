<?php

namespace App\Services\Employee;

use App\Exceptions\IncompleteOffboardingChecklistException;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeOffboarding;
use App\Repositories\Contracts\EmployeeContractRepositoryInterface;
use App\Repositories\Contracts\EmployeeOffboardingRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\OffboardingChecklistRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeOffboardingService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeOffboardingRepositoryInterface $offboardings,
        private readonly OffboardingChecklistRepositoryInterface $checklist,
        private readonly EmployeeContractRepositoryInterface $contracts,
        private readonly EmployeeNoteService $notes
    ) {}

    /**
     * @return array{offboarding: EmployeeOffboarding|null, is_visible: bool}
     */
    public function current(int $employeeId): array
    {
        $employee = $this->employees->show($employeeId);

        return [
            'offboarding' => $this->offboardings->activeForEmployee($employee),
            'is_visible' => $this->offboardings->hasAnyHistoryForEmployee($employee),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function initiate(int $employeeId, array $data): EmployeeOffboarding
    {
        $employee = $this->employees->show($employeeId);

        if ($this->offboardings->activeForEmployee($employee) !== null) {
            throw new InvalidArgumentException('employee.offboarding.active_exists');
        }

        $offboarding = $this->offboardings->createForEmployee($employee, array_merge(
            $this->offboardingPayload($data),
            [
                'status' => EmployeeOffboarding::STATUS_DRAFT,
                'initiated_by' => Auth::id(),
                'initiated_at' => now(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        ));

        $this->notes->storeSystemForEmployee($employee, $this->initiationNote($offboarding));

        return $offboarding;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $employeeId, int $offboardingId, array $data): EmployeeOffboarding
    {
        $employee = $this->employees->show($employeeId);
        $offboarding = $this->offboardings->findForEmployee($employee, $offboardingId);

        $this->ensureNotCompleted($offboarding);

        return $this->offboardings->update($offboarding, array_merge($this->offboardingPayload($data), [
            'updated_by' => Auth::id(),
        ]));
    }

    public function complete(int $employeeId, int $offboardingId): EmployeeOffboarding
    {
        return DB::transaction(function () use ($employeeId, $offboardingId): EmployeeOffboarding {
            $employee = $this->employees->show($employeeId);
            $offboarding = $this->offboardings->findForEmployee($employee, $offboardingId);

            $this->ensureNotCompleted($offboarding);
            $this->ensureChecklistCompleted($offboarding);

            foreach ($this->contracts->activeForEmployee($employee) as $activeContract) {
                $superseded = $this->contracts->update($activeContract, [
                    'status' => EmployeeContract::STATUS_SUPERSEDED,
                    'updated_by' => Auth::id(),
                ]);
                $this->contracts->archive($superseded);
            }

            $this->employees->update($employee, [
                'status' => Employee::INACTIVE,
                'end_date' => $offboarding->getAttribute('last_working_date'),
                'updated_by' => Auth::id(),
            ]);

            $completed = $this->offboardings->update($offboarding, [
                'status' => EmployeeOffboarding::STATUS_COMPLETED,
                'completed_by' => Auth::id(),
                'completed_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            $this->notes->storeSystemForEmployee($employee->refresh(), $this->completionNote($completed));

            return $completed;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function offboardingPayload(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'reason_type',
            'reason_detail',
            'last_working_date',
            'notes',
        ]));
    }

    private function ensureNotCompleted(EmployeeOffboarding $offboarding): void
    {
        if ($offboarding->getAttribute('status') === EmployeeOffboarding::STATUS_COMPLETED) {
            throw new InvalidArgumentException('employee.offboarding.already_completed');
        }
    }

    private function ensureChecklistCompleted(EmployeeOffboarding $offboarding): void
    {
        $incomplete = $this->checklist->incompleteForOffboarding($offboarding);

        if ($incomplete->isEmpty()) {
            return;
        }

        throw new IncompleteOffboardingChecklistException($incomplete
            ->map(fn ($item): array => [
                'id' => $item->getKey(),
                'title' => $item->getAttribute('title'),
                'assigned_to' => $item->getAttribute('assigned_to'),
                'due_date' => $item->getAttribute('due_date'),
            ])
            ->values()
            ->all());
    }

    private function initiationNote(EmployeeOffboarding $offboarding): string
    {
        $content = 'Offboarding dimulai: '.$offboarding->getAttribute('reason_type');
        $detail = $offboarding->getAttribute('reason_detail');

        return is_string($detail) && $detail !== '' ? $content.' - '.$detail : $content;
    }

    private function completionNote(EmployeeOffboarding $offboarding): string
    {
        $content = 'Offboarding selesai: '.$offboarding->getAttribute('reason_type');

        if ($offboarding->getAttribute('reason_type') !== EmployeeOffboarding::REASON_TERMINATION) {
            return $content;
        }

        $detail = $offboarding->getAttribute('reason_detail') ?: '-';
        $lastWorkingDate = $offboarding->getAttribute('last_working_date')?->toDateString();

        return $content.' - Detail: '.$detail.' - Tanggal kerja terakhir: '.$lastWorkingDate;
    }
}
