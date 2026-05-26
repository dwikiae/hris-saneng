<?php

namespace App\Application\Employee;

use App\Domain\Employee\EmployeeStatus;
use App\Models\Employee;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\JobPosting;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class CreateEmployeeFromApplicantService
{
    public function __construct(private readonly EmployeeRepositoryInterface $employees) {}

    public function execute(Applicant $applicant, int $userId): Employee
    {
        $jobPosting = $applicant->getRelation('jobPosting');
        $name = (string) $applicant->getAttribute('name');

        $employee = $this->employees->create([
            'company_id' => (int) $applicant->getAttribute('company_id'),
            'employee_number' => null,
            'full_name' => $name,
            'name' => $name,
            'email' => $applicant->getAttribute('email'),
            'phone' => $applicant->getAttribute('phone'),
            'birth_date' => $applicant->getAttribute('birth_date'),
            'birth_place' => $applicant->getAttribute('birth_place'),
            'gender' => $applicant->getAttribute('gender') ?: 'male',
            'position_id' => $jobPosting instanceof JobPosting ? $jobPosting->getAttribute('position_id') : null,
            'nik' => $applicant->getAttribute('nik') ?: '0000000000000000',
            'consent_at' => $applicant->getAttribute('consent_at') ?? now(),
            'consent_by' => $userId,
            'consent_text' => 'Consent imported from recruitment applicant.',
            'status' => EmployeeStatus::Pending->value,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->employees->createSystemLog($employee, 'Draft karyawan dibuat dari applicant '.$applicant->getAttribute('application_number'));

        return $employee;
    }
}
