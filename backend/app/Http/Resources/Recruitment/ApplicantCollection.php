<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApplicantCollection extends ResourceCollection
{
    public $collects = ApplicantResource::class;
}
