<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateInstanceSettingsRequest;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\User;
use App\Repositories\Contracts\InstanceSettingsRepositoryInterface;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
        private readonly InstanceSettingsRepositoryInterface $instanceSettings,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('settings.view');

        $companyId = $this->settingsCompanyId($request);

        return $this->success($this->maskedSettings($this->settings->getAllForCompany($companyId)), 'settings.list');
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $companyId = $this->settingsCompanyId($request);

        $this->settings->setManyForCompany($request->validated(), $companyId);

        return $this->success($this->maskedSettings($this->settings->getAllForCompany($companyId)), 'settings.updated');
    }

    public function instanceIndex(Request $request): JsonResponse
    {
        Gate::authorize('settings.view');

        if (! $this->isInstanceAdmin($request)) {
            return $this->forbidden();
        }

        return $this->success($this->maskedSettings($this->instanceSettings->getAll()), 'settings.instance_list');
    }

    public function instanceUpdate(UpdateInstanceSettingsRequest $request): JsonResponse
    {
        $this->instanceSettings->setMany($request->validated());

        return $this->success($this->maskedSettings($this->instanceSettings->getAll()), 'settings.instance_updated');
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

    private function forbidden(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'settings.instance_forbidden'], 403);
    }

    private function settingsCompanyId(Request $request): int
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isInstanceAdmin()) {
            return (int) $user->company_id;
        }

        return (int) config('company.default_id', 1);
    }

    private function isInstanceAdmin(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && $user->isInstanceAdmin();
    }
}
