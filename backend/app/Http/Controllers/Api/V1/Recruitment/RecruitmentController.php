<?php

namespace App\Http\Controllers\Api\V1\Recruitment;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

abstract class RecruitmentController extends Controller
{
    protected function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }

    protected function fail(InvalidArgumentException $exception): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
    }
}
