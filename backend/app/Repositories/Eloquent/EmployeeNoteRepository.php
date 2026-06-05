<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeNote;
use App\Repositories\Contracts\EmployeeNoteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EmployeeNoteRepository implements EmployeeNoteRepositoryInterface
{
    /**
     * @return Collection<int, EmployeeNote>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return $employee->notes()
            ->with('createdBy')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeNote
    {
        /** @var EmployeeNote $note */
        $note = $employee->notes()->create(array_merge($data, [
            'company_id' => $employee->getAttribute('company_id'),
        ]));

        return $note->load('createdBy');
    }
}
