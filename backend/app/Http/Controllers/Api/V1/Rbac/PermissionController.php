<?php

namespace App\Http\Controllers\Api\V1\Rbac;

use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\PermissionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionRepository $repository) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [];

        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }

        if ($request->has('module')) {
            $filters['module'] = $request->string('module')->toString();
        }

        return response()->json([
            'success' => true,
            'data' => $this->repository->index($filters),
            'message' => 'permission.list',
        ]);
    }
}
