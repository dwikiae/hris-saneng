<?php

namespace App\Http\Controllers\Api\V1\Recruitment;

use App\Application\Recruitment\TestService;
use App\Http\Requests\Recruitment\StoreTestQuestionRequest;
use App\Http\Requests\Recruitment\StoreTestRequest;
use App\Http\Resources\Recruitment\TestQuestionResource;
use App\Http\Resources\Recruitment\TestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class TestController extends RecruitmentController
{
    public function __construct(private readonly TestService $service) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('recruitment.test.view');
        return $this->success(TestResource::collection($this->service->index($request->query())), 'recruitment.test.list');
    }

    public function store(StoreTestRequest $request): JsonResponse
    {
        return $this->success(TestResource::make($this->service->createTest($request->validated(), (int) $request->user()?->getKey())), 'recruitment.test.created', 201);
    }

    public function show(int $id): JsonResponse
    {
        Gate::authorize('recruitment.test.view');
        return $this->success(TestResource::make($this->service->show($id)), 'recruitment.test.detail');
    }

    public function update(StoreTestRequest $request, int $id): JsonResponse
    {
        try {
            return $this->success(TestResource::make($this->service->updateTest($id, $request->validated(), (int) $request->user()?->getKey())), 'recruitment.test.updated');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }

    public function addQuestion(StoreTestQuestionRequest $request, int $id): JsonResponse
    {
        return $this->success(TestQuestionResource::make($this->service->addQuestion($id, $request->validated(), (int) $request->user()?->getKey())), 'recruitment.test.question_created', 201);
    }

    public function updateQuestion(StoreTestQuestionRequest $request, int $id, int $qid): JsonResponse
    {
        return $this->success(TestQuestionResource::make($this->service->updateQuestion($qid, $request->validated(), (int) $request->user()?->getKey())), 'recruitment.test.question_updated');
    }

    public function deleteQuestion(Request $request, int $id, int $qid): JsonResponse
    {
        $this->service->deleteQuestion($qid, (int) $request->user()?->getKey());
        return $this->success(null, 'recruitment.test.question_deleted');
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $this->service->archiveTest($id, (int) $request->user()?->getKey());
        return $this->success(null, 'recruitment.test.archived');
    }
}
