<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Application\Recruitment\ApplicantService;
use App\Http\Requests\Recruitment\ConfirmInterviewRequest;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class PublicInterviewController extends PublicController
{
    public function __construct(private readonly ApplicantService $service) {}

    public function show(string $token): JsonResponse
    {
        return $this->success(['token' => $token], 'recruitment.interview.detail');
    }

    public function confirm(ConfirmInterviewRequest $request, string $token): JsonResponse
    {
        try {
            $this->service->confirmInterviewAttendance($token, (string) $request->validated('confirmation'));
            return $this->success(null, 'recruitment.interview.confirmed');
        } catch (InvalidArgumentException $exception) {
            return $this->fail($exception);
        }
    }
}
