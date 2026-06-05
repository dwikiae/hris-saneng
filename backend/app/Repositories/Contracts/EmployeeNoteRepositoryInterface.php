<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeNote;
use Illuminate\Database\Eloquent\Collection;

interface EmployeeNoteRepositoryInterface
{
    /**
     * @return Collection<int, EmployeeNote>
     */
    public function listForEmployee(Employee $employee): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeNote;
}
