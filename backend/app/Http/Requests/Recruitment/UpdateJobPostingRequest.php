<?php

namespace App\Http\Requests\Recruitment;

class UpdateJobPostingRequest extends StoreJobPostingRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = array_values(array_filter($fieldRules, fn (string $rule): bool => $rule !== 'required'));
            array_unshift($rules[$field], 'sometimes');
        }

        return $rules;
    }
}
