<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Application\Recruitment\JobPostingService;
use App\Http\Resources\Recruitment\JobPostingResource;
use Illuminate\Http\JsonResponse;

class PublicJobController extends PublicController
{
    public function __construct(private readonly JobPostingService $service) {}

    public function index(): JsonResponse
    {
        return $this->success(JobPostingResource::collection($this->service->index()), 'recruitment.job_posting.list');
    }
}
