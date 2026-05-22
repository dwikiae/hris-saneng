<?php

namespace App\Http\Requests\Recruitment;

class StoreTestQuestionRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:multiple_choice,short_answer,pilihan_ganda,isian_singkat'],
            'question' => ['required', 'string'],
            'pilihan' => ['nullable', 'array'],
            'answer_key' => ['required', 'string'],
            'bobot' => ['required', 'numeric', 'min:0'],
        ];
    }
}
