<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Karyawan\Application\EmployeeModuleSettingsService;
use App\Modules\Karyawan\Http\Requests\UpdateEmployeeModuleSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeModuleSettingsController extends Controller
{
    public function __construct(private readonly EmployeeModuleSettingsService $settings) {}

    public function show(Request $request): JsonResponse
    {
        Gate::authorize('karyawan.settings');

        return $this->success(
            $this->settings->get((int) $request->attributes->get('company_id')),
            'karyawan.settings.detail'
        );
    }

    public function update(UpdateEmployeeModuleSettingsRequest $request): JsonResponse
    {
        return $this->success(
            $this->settings->update((int) $request->attributes->get('company_id'), $request->validated()),
            'karyawan.settings.updated'
        );
    }

    private function success(mixed $data, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => [],
        ]);
    }
}
