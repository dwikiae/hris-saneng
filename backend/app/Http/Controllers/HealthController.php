<?php

namespace App\Http\Controllers;

use App\Core\Health\Application\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function check(HealthCheckService $healthCheckService): JsonResponse
    {
        $health = $healthCheckService->check();

        return response()->json(
            $health,
            $health['status'] === 'ok' ? 200 : 503
        );
    }
}
