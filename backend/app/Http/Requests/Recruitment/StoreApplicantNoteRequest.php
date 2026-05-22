<?php

namespace App\Http\Requests\Recruitment;

class StoreApplicantNoteRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return ['catatan' => ['required', 'string']];
    }
}
