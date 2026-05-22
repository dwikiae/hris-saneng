<?php

namespace App\Http\Requests\Recruitment;

class SubmitApplicationRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return [
            'job_posting_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:20'],
            'cv' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'consent_at' => ['nullable', 'date'],
        ];
    }
}
