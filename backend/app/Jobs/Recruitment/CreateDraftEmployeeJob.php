<?php

namespace App\Jobs\Recruitment;

use App\Models\Employee;
use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\JobPosting;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CreateDraftEmployeeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $applicantId, public readonly int $userId) {}

    public function handle(
        ApplicantRepositoryInterface $applicants,
        EmployeeRepositoryInterface $employees
    ): void {
        DB::transaction(function () use ($applicants, $employees): void {
            $applicant = $applicants->findByIdWithPipelineRelations($this->applicantId);

            if (! $applicant instanceof Applicant) {
                throw (new ModelNotFoundException)->setModel(Applicant::class, $this->applicantId);
            }

            $jobPosting = $applicant->getRelation('jobPosting');

            $employee = $employees->create([
                'company_id' => (int) config('app.company_id'),
                'employee_number' => $this->draftEmployeeNumber($applicant),
                'name' => (string) $applicant->getAttribute('name'),
                'email' => $applicant->getAttribute('email'),
                'phone' => $applicant->getAttribute('phone'),
                'birth_date' => $applicant->getAttribute('birth_date'),
                'birth_place' => $applicant->getAttribute('birth_place'),
                'gender' => $applicant->getAttribute('gender'),
                'position_id' => $jobPosting instanceof JobPosting ? $jobPosting->getAttribute('position_id') : null,
                'consent_at' => $applicant->getAttribute('consent_at') ?? now(),
                'consent_by' => $this->userId,
                'status' => Employee::DRAFT,
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);

            activity()
                ->useLog('employees')
                ->performedOn($employee)
                ->event('draft_created_from_applicant')
                ->withProperties([
                    'applicant_id' => $applicant->getKey(),
                    'created_by' => $this->userId,
                ])
                ->log('employees.draft_created_from_applicant');
        });
    }

    private function draftEmployeeNumber(Applicant $applicant): string
    {
        return 'DRAFT-'.$applicant->getAttribute('application_number');
    }
}
