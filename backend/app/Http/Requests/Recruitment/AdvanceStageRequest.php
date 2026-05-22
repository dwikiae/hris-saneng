<?php

namespace App\Http\Requests\Recruitment;

class AdvanceStageRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return [
            'to_stage' => ['required', 'string', 'in:screening,tes_tulis,interview,tes_kemampuan,mcu,pemberkasan,rejected'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
