<?php

namespace App\Http\Requests\Recruitment;

class StoreTestRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'passing_grade' => ['required', 'numeric', 'min:0'],
        ];
    }
}
