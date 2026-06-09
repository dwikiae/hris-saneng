<?php

namespace App\Modules\Karyawan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Karyawan\Application\EmployeeModuleSettingsService;
use App\Modules\Karyawan\Http\Requests\PreviewEmployeeNumberFormatRequest;
use App\Modules\Karyawan\Http\Requests\UpdateEmployeeModuleSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

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
        try {
            return $this->success(
                $this->settings->update((int) $request->attributes->get('company_id'), $request->validated()),
                'karyawan.settings.updated'
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    public function previewNumberFormat(PreviewEmployeeNumberFormatRequest $request): JsonResponse
    {
        try {
            return $this->success(
                $this->settings->preview(
                    (int) $request->attributes->get('company_id'),
                    (string) ($request->validated('format') ?? '')
                ),
                'karyawan.settings.number_format_preview'
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
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

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'meta' => [],
        ], $status);
    }
}
