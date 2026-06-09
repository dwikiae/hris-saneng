<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use App\Repositories\Contracts\EmployeeEmergencyContactRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EmployeeEmergencyContactService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeEmergencyContactRepositoryInterface $contacts
    ) {}

    /**
     * @return Collection<int, EmployeeEmergencyContact>
     */
    public function list(int $employeeId): Collection
    {
        $employee = $this->employees->show($employeeId);

        return $this->contacts->listForEmployee($employee);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(int $employeeId, array $data): EmployeeEmergencyContact
    {
        $employee = $this->employees->show($employeeId);

        return $this->storeForEmployee($employee, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeForEmployee(Employee $employee, array $data): EmployeeEmergencyContact
    {
        return $this->contacts->createForEmployee($employee, array_merge($this->payload($data), [
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $employeeId, int $contactId, array $data): EmployeeEmergencyContact
    {
        $employee = $this->employees->show($employeeId);
        $contact = $this->contacts->findForEmployee($employee, $contactId);

        return $this->contacts->update($contact, array_merge($this->payload($data), [
            'updated_by' => Auth::id(),
        ]));
    }

    public function archive(int $employeeId, int $contactId): void
    {
        $employee = $this->employees->show($employeeId);
        $contact = $this->contacts->findForEmployee($employee, $contactId);

        $this->contacts->archive($contact);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'name',
            'relationship',
            'phone',
        ]));
    }
}
