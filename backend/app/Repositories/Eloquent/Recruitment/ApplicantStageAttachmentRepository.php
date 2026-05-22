<?php

namespace App\Repositories\Eloquent\Recruitment;

use App\Models\Recruitment\ApplicantStageAttachment;
use App\Repositories\Contracts\Recruitment\ApplicantStageAttachmentRepositoryInterface;

class ApplicantStageAttachmentRepository implements ApplicantStageAttachmentRepositoryInterface
{
    public function __construct(private readonly ApplicantStageAttachment $model) {}

    public function create(array $data): ApplicantStageAttachment
    {
        /** @var ApplicantStageAttachment $attachment */
        $attachment = $this->model->newQuery()->create($data);

        return $attachment;
    }
}
