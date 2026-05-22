<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Recruitment\TestQuestion;

class TestQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $question = $this->resource instanceof TestQuestion ? $this->resource : null;

        return [
            'id' => $question?->getKey(),
            'type' => $question?->getAttribute('type'),
            'question' => $question?->getAttribute('question'),
            'pilihan' => $question?->getAttribute('pilihan'),
            'bobot' => $question?->getAttribute('bobot'),
        ];
    }
}
