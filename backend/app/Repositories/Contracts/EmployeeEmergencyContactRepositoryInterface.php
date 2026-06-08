<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use Illuminate\Database\Eloquent\Collection;

interface EmployeeEmergencyContactRepositoryInterface
{
    /**
     * @return Collection<int, EmployeeEmergencyContact>
     */
    public function listForEmployee(Employee $employee): Collection;

    public function findForEmployee(Employee $employee, int $contactId): EmployeeEmergencyContact;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeEmergencyContact;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeEmergencyContact $contact, array $data): EmployeeEmergencyContact;

    public function archive(EmployeeEmergencyContact $contact): void;
}
