<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    public function index(): JsonResponse
    {
        Gate::authorize('settings.view');

        return $this->success($this->maskedSettings($this->settings->getAll()), 'settings.list');
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->settings->setMany($request->validated());

        return $this->success($this->maskedSettings($this->settings->getAll()), 'settings.updated');
    }

    /**
     * @param  Collection<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function maskedSettings(Collection $settings): array
    {
        $data = $settings->all();

        $data['smtp_password'] = empty($data['smtp_password']) ? null : '*****';

        return $data;
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }
}
