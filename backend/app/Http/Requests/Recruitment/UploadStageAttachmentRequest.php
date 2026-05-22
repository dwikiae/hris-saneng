<?php

namespace App\Http\Requests\Recruitment;

class UploadStageAttachmentRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'stage' => ['nullable', 'string'],
        ];
    }
}
