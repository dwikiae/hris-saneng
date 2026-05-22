<?php

namespace App\Http\Requests\Recruitment;

class ScheduleInterviewRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return [
            'tanggal_interview' => ['required_without:scheduled_at', 'date'],
            'scheduled_at' => ['required_without:tanggal_interview', 'date'],
            'lokasi_atau_platform' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
