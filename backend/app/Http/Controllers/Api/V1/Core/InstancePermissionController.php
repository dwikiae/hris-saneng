<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Application\Instance\PermissionStructureService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instance\ShowPermissionStructureRequest;
use Illuminate\Http\JsonResponse;

class InstancePermissionController extends Controller
{
    public function __construct(private readonly PermissionStructureService $permissions) {}

    public function structure(ShowPermissionStructureRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->permissions->structure($request->integer('company_id') ?: null),
            'message' => 'instance.permission.structure',
            'meta' => [],
        ]);
    }
}
