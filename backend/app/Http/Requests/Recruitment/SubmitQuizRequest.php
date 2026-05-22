<?php

namespace App\Http\Requests\Recruitment;

class SubmitQuizRequest extends RecruitmentRequest
{
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*.test_question_id' => ['required_without:answers.*.question_id', 'integer'],
            'answers.*.question_id' => ['required_without:answers.*.test_question_id', 'integer'],
            'answers.*.answer' => ['required_without:answers.*.jawaban', 'string'],
            'answers.*.jawaban' => ['required_without:answers.*.answer', 'string'],
        ];
    }
}
