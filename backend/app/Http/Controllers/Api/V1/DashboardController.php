<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Dashboard\DashboardStatsService;
use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardStatsResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardStatsService $dashboardStats) {}

    public function stats(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $companyId = (int) $request->attributes->get('company_id');

        return response()->json([
            'success' => true,
            'data' => DashboardStatsResource::make($this->dashboardStats->stats($companyId, $user))->resolve($request),
            'message' => 'dashboard.stats',
            'meta' => [],
        ]);
    }
}
