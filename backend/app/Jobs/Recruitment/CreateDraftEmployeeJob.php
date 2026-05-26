<?php

namespace App\Jobs\Recruitment;

use App\Application\Employee\CreateEmployeeFromApplicantService;
use App\Models\Recruitment\Applicant;
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
        CreateEmployeeFromApplicantService $createEmployeeFromApplicant
    ): void {
        DB::transaction(function () use ($applicants, $createEmployeeFromApplicant): void {
            $applicant = $applicants->findByIdWithPipelineRelations($this->applicantId);

            if (! $applicant instanceof Applicant) {
                throw (new ModelNotFoundException)->setModel(Applicant::class, $this->applicantId);
            }

            $employee = $createEmployeeFromApplicant->execute($applicant, $this->userId);

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
}
