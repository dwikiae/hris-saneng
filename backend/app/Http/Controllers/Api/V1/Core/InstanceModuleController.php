<?php

namespace App\Http\Controllers\Api\V1\Core;

use App\Application\Instance\InstanceModuleService;
use App\Application\Instance\ModuleLifecycleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instance\ListInstanceModulesRequest;
use App\Http\Requests\Instance\ManageInstanceModuleRequest;
use App\Http\Resources\InstanceModuleResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class InstanceModuleController extends Controller
{
    public function __construct(private readonly InstanceModuleService $modules) {}

    public function index(ListInstanceModulesRequest $request): JsonResponse
    {
        return $this->success(
            InstanceModuleResource::collection($this->modules->list())->resolve($request),
            'instance.module.list'
        );
    }

    public function install(ManageInstanceModuleRequest $request, string $code): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $module = $this->modules->install($code, $user);
        } catch (ModuleLifecycleException $exception) {
            return $this->error($exception);
        }

        return $this->success(
            InstanceModuleResource::make($module)->resolve($request),
            'instance.module.installed'
        );
    }

    public function exportData(ManageInstanceModuleRequest $request, string $code): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $export = $this->modules->exportData($code, $user);
        } catch (ModuleLifecycleException $exception) {
            return $this->error($exception);
        }

        return $this->success($export, 'instance.module.exported');
    }

    public function uninstall(ManageInstanceModuleRequest $request, string $code): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->modules->uninstall($code, $user);
        } catch (ModuleLifecycleException $exception) {
            return $this->error($exception);
        }

        return $this->success(null, 'instance.module.uninstalled');
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => [],
        ], $status);
    }

    private function error(ModuleLifecycleException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $exception->getMessage(),
            'meta' => $exception->meta(),
        ], $exception->status());
    }
}
