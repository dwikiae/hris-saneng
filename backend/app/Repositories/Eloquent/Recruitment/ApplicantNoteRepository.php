<?php

namespace App\Repositories\Eloquent\Recruitment;

use App\Models\Recruitment\ApplicantNote;
use App\Repositories\Contracts\Recruitment\ApplicantNoteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ApplicantNoteRepository implements ApplicantNoteRepositoryInterface
{
    public function __construct(private readonly ApplicantNote $model) {}

    public function listByApplicant(int $applicantId): Collection
    {
        /** @var Collection<int, ApplicantNote> $notes */
        $notes = $this->model->newQuery()
            ->where('company_id', $this->defaultCompanyId())
            ->where('applicant_id', $applicantId)
            ->orderByDesc('created_at')
            ->get();

        return $notes;
    }

    public function create(array $data): ApplicantNote
    {
        /** @var ApplicantNote $note */
        $note = $this->model->newQuery()->create($data);

        return $note;
    }

    private function defaultCompanyId(): int
    {
        return (int) config('app.company_id');
    }
}
