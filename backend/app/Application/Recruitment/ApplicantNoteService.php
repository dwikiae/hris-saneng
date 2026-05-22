<?php

namespace App\Application\Recruitment;

use App\Models\Recruitment\Applicant;
use App\Models\Recruitment\ApplicantNote;
use App\Repositories\Contracts\Recruitment\ApplicantNoteRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApplicantNoteService
{
    public function __construct(
        private readonly ApplicantRepositoryInterface $applicants,
        private readonly ApplicantNoteRepositoryInterface $notes
    ) {}

    public function addNote(int $applicantId, string $catatan, int $actorId): ApplicantNote
    {
        Gate::authorize('recruitment.notes.create');

        return DB::transaction(function () use ($applicantId, $catatan, $actorId): ApplicantNote {
            $applicant = $this->findApplicant($applicantId);

            return $this->notes->create([
                'company_id' => (int) config('app.company_id'),
                'applicant_id' => $applicant->getKey(),
                'user_id' => $actorId,
                'stage' => $this->stageValue($applicant),
                'note' => $catatan,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        });
    }

    /**
     * @return Collection<int, ApplicantNote>
     */
    public function getNotes(int $applicantId, int $actorId): Collection
    {
        Gate::authorize('recruitment.notes.view');
        $this->findApplicant($applicantId);

        return $this->notes->listByApplicant($applicantId);
    }

    private function findApplicant(int $applicantId): Applicant
    {
        $applicant = $this->applicants->findByIdWithPipelineRelations($applicantId);

        if ($applicant === null) {
            throw (new ModelNotFoundException)->setModel(Applicant::class, $applicantId);
        }

        return $applicant;
    }

    private function stageValue(Applicant $applicant): string
    {
        $stage = $applicant->getAttribute('stage');

        return $stage instanceof \BackedEnum ? (string) $stage->value : (string) $stage;
    }
}
