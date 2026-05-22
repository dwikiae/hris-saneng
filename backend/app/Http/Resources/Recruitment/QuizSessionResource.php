<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'] ?? null,
            'timer' => $this->resource['timer'] ?? null,
            'questions' => collect($this->resource['questions'] ?? [])->shuffle()->values()->all(),
        ];
    }
}
