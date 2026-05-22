<?php

namespace App\Http\Requests\Recruitment;

class ConfirmInterviewRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return ['confirmation' => ['required', 'string', 'in:hadir,tidak_hadir']];
    }
}
