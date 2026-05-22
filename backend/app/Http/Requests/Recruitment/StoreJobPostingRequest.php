<?php

namespace App\Http\Requests\Recruitment;

class StoreJobPostingRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'position_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'test_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'expired_at' => ['nullable', 'date'],
        ];
    }
}
