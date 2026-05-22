<?php

namespace App\Http\Controllers\Api\V1\Recruitment;

use App\Application\Recruitment\BlacklistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BlacklistController extends RecruitmentController
{
    public function __construct(private readonly BlacklistService $service) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('recruitment.blacklist.view');
        return $this->success($this->service->index($request->query()), 'recruitment.blacklist.list');
    }
}
