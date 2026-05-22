<?php

namespace App\Repositories\Contracts\Recruitment;

use App\Models\Recruitment\ApplicantNote;
use Illuminate\Database\Eloquent\Collection;

interface ApplicantNoteRepositoryInterface
{
    /**
     * @return Collection<int, ApplicantNote>
     */
    public function listByApplicant(int $applicantId): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ApplicantNote;
}
