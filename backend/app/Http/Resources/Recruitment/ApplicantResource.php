<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Recruitment\Applicant;

class ApplicantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $applicant = $this->resource instanceof Applicant ? $this->resource : null;
        $stage = $applicant?->getAttribute('stage');
        $status = $applicant?->getAttribute('status');

        return [
            'id' => $applicant?->getKey(),
            'application_number' => $applicant?->getAttribute('application_number'),
            'name' => $applicant?->getAttribute('name'),
            'email' => $applicant?->getAttribute('email'),
            'phone' => $applicant?->getAttribute('phone'),
            'stage' => $stage instanceof \BackedEnum ? $stage->value : $stage,
            'status' => $status instanceof \BackedEnum ? $status->value : $status,
        ];
    }
}
