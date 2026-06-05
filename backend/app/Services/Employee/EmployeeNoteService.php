<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\EmployeeNote;
use App\Repositories\Contracts\EmployeeNoteRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EmployeeNoteService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeNoteRepositoryInterface $notes
    ) {}

    /**
     * @return Collection<int, EmployeeNote>
     */
    public function list(int $employeeId): Collection
    {
        $employee = $this->employees->show($employeeId);

        return $this->notes->listForEmployee($employee);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeManual(int $employeeId, array $data): EmployeeNote
    {
        $employee = $this->employees->show($employeeId);

        return $this->createSystemOrManual($employee, EmployeeNote::TYPE_MANUAL, [
            'content' => $data['content'],
            'mentioned_users' => $data['mentioned_users'] ?? null,
        ]);
    }

    public function storeSystemForEmployee(Employee $employee, string $content): EmployeeNote
    {
        return $this->createSystemOrManual($employee, EmployeeNote::TYPE_SYSTEM, [
            'content' => $content,
            'mentioned_users' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createSystemOrManual(Employee $employee, string $type, array $data): EmployeeNote
    {
        return $this->notes->createForEmployee($employee, [
            'content' => $data['content'],
            'type' => $type,
            'mentioned_users' => $data['mentioned_users'],
            'created_by' => Auth::id(),
        ]);
    }
}
