<?php

namespace App\Http\Controllers\Api\V1\Recruitment;

use App\Application\Recruitment\JobPostingService;
use App\Http\Requests\Recruitment\StoreJobPostingRequest;
use App\Http\Requests\Recruitment\UpdateJobPostingRequest;
use App\Http\Resources\Recruitment\JobPostingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class JobPostingController extends RecruitmentController
{
    public function __construct(private readonly JobPostingService $service) {}

    public function index(): JsonResponse
    {
        Gate::authorize('recruitment.job_posting.view');
        return $this->success(JobPostingResource::collection($this->service->index()), 'recruitment.job_posting.list');
    }

    public function store(StoreJobPostingRequest $request): JsonResponse
    {
        Gate::authorize('recruitment.job_posting.create');
        return $this->success(JobPostingResource::make($this->service->createDraft($request->validated(), (int) $request->user()?->getKey())), 'recruitment.job_posting.created', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        Gate::authorize('recruitment.job_posting.view');
        return $this->success(JobPostingResource::make($this->service->show($id)), 'recruitment.job_posting.detail');
    }

    public function update(UpdateJobPostingRequest $request, int $id): JsonResponse
    {
        Gate::authorize('recruitment.job_posting.update');
        try {
            return $this->success(JobPostingResource::make($this->service->update($id, $request->validated(), (int) $request->user()?->getKey())), 'recruitment.job_posting.updated');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        try {
            return $this->success(JobPostingResource::make($this->service->publish($id, (int) $request->user()?->getKey())), 'recruitment.job_posting.published');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }

    public function unpublish(Request $request, int $id): JsonResponse
    {
        Gate::authorize('recruitment.job_posting.update');
        try {
            return $this->success(JobPostingResource::make($this->service->unpublish($id, (int) $request->user()?->getKey())), 'recruitment.job_posting.unpublished');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        try {
            return $this->success(JobPostingResource::make($this->service->archive($id, (int) $request->user()?->getKey())), 'recruitment.job_posting.archived');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }
}
