<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Resources\Json\ResourceCollection;

class JobPostingCollection extends ResourceCollection
{
    public $collects = JobPostingResource::class;
}
