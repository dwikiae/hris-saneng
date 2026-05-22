<?php

namespace App\Repositories\Contracts\Recruitment;

use App\Models\Recruitment\ApplicantStageAttachment;

interface ApplicantStageAttachmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ApplicantStageAttachment;
}
