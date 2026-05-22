<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Recruitment\ApplicantNote;

class ApplicantNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $note = $this->resource instanceof ApplicantNote ? $this->resource : null;
        $stage = $note?->getAttribute('stage');

        return [
            'id' => $note?->getKey(),
            'applicant_id' => $note?->getAttribute('applicant_id'),
            'stage' => $stage instanceof \BackedEnum ? $stage->value : $stage,
            'note' => $note?->getAttribute('note'),
            'created_at' => $note?->getAttribute('created_at'),
        ];
    }
}
