<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Recruitment\JobPosting;

class JobPostingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $jobPosting = $this->resource instanceof JobPosting ? $this->resource : null;
        $status = $jobPosting?->getAttribute('status');

        return [
            'id' => $jobPosting?->getKey(),
            'code' => $jobPosting?->getAttribute('code'),
            'title' => $jobPosting?->getAttribute('title'),
            'status' => $status instanceof \BackedEnum ? $status->value : $status,
            'expired_at' => $jobPosting?->getAttribute('expired_at'),
        ];
    }
}
