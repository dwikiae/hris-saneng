<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeEmergencyContact;
use App\Repositories\Contracts\EmployeeEmergencyContactRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Collection;

class EmployeeEmergencyContactRepository implements EmployeeEmergencyContactRepositoryInterface
{
    public function __construct(private readonly ArchiveService $archiveService) {}

    /**
     * @return Collection<int, EmployeeEmergencyContact>
     */
    public function listForEmployee(Employee $employee): Collection
    {
        return $employee->emergencyContacts()
            ->orderBy('name')
            ->get();
    }

    public function findForEmployee(Employee $employee, int $contactId): EmployeeEmergencyContact
    {
        /** @var EmployeeEmergencyContact $contact */
        $contact = $employee->emergencyContacts()->findOrFail($contactId);

        return $contact;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForEmployee(Employee $employee, array $data): EmployeeEmergencyContact
    {
        /** @var EmployeeEmergencyContact $contact */
        $contact = $employee->emergencyContacts()->create(array_merge($data, [
            'company_id' => $employee->getAttribute('company_id'),
        ]));

        return $contact;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeEmergencyContact $contact, array $data): EmployeeEmergencyContact
    {
        $contact->update($data);

        return $contact->refresh();
    }

    public function archive(EmployeeEmergencyContact $contact): void
    {
        $this->archiveService->archive($contact);
    }
}
