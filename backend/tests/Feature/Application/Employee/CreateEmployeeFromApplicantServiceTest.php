<?php

use App\Application\Employee\CreateEmployeeFromApplicantService;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\JobPosting;
use App\Models\Recruitment\Test as RecruitmentTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates pending employee from hired applicant without payroll fields or employee number', function () {
    $company = Company::create(['name' => 'PT Saneng', 'legal_name' => 'PT Saneng']);
    config(['app.company_id' => $company->id, 'company.default_id' => $company->id]);
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Recruiter',
        'email' => 'recruiter@example.test',
        'password' => 'password',
    ]);
    $position = Position::create(['company_id' => $company->id, 'code' => 'STAFF', 'name' => 'Staff']);
    $test = RecruitmentTest::create(['company_id' => $company->id, 'code' => 'TEST', 'name' => 'Test']);
    $jobPosting = JobPosting::create([
        'company_id' => $company->id,
        'position_id' => $position->id,
        'test_id' => $test->id,
        'code' => 'JOB-001',
        'title' => 'Staff',
        'status' => 'published',
    ]);
    $applicant = Applicant::create([
        'company_id' => $company->id,
        'job_posting_id' => $jobPosting->id,
        'application_number' => 'APP-001',
        'name' => 'Applicant Employee',
        'email' => 'applicant@example.test',
        'phone' => '628123',
        'nik' => '3374010101010001',
        'stage' => 'hired',
        'status' => 'active',
        'consent_at' => now(),
    ]);

    $employee = app(CreateEmployeeFromApplicantService::class)->execute($applicant->load('jobPosting'), $user->id);

    expect($employee->status)->toBe(Employee::PENDING)
        ->and($employee->employee_number)->toBeNull()
        ->and($employee->full_name)->toBe('Applicant Employee')
        ->and($employee->position_id)->toBe($position->id)
        ->and($employee->getAttributes())->not->toHaveKeys(['salary', 'wage', 'allowances', 'deductions']);
});
