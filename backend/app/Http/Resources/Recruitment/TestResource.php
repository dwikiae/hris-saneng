<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Recruitment\Test;

class TestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $test = $this->resource instanceof Test ? $this->resource : null;

        return [
            'id' => $test?->getKey(),
            'code' => $test?->getAttribute('code'),
            'name' => $test?->getAttribute('name'),
            'duration_minutes' => $test?->getAttribute('duration_minutes'),
            'passing_grade' => $test?->getAttribute('passing_grade'),
            'questions' => TestQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
