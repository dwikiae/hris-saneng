<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;

abstract class RecruitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
