<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Application\Recruitment\ApplicantService;
use App\Http\Requests\Recruitment\SubmitApplicationRequest;
use App\Http\Resources\Recruitment\ApplicantResource;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class PublicApplicationController extends PublicController
{
    public function __construct(private readonly ApplicantService $service) {}

    public function submit(SubmitApplicationRequest $request): JsonResponse
    {
        try {
            return $this->success(ApplicantResource::make($this->service->submitApplication($request->validated())), 'recruitment.application.submitted', 201);
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }
}
