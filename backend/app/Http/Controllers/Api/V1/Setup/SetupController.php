<?php

namespace App\Http\Controllers\Api\V1\Setup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setup\CompleteSetupRequest;
use App\Models\Company;
use App\Models\User;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\InstanceSettingsRepositoryInterface;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class SetupController extends Controller
{
    private const MANDATORY_MODULES = [
        'karyawan',
        'kalender',
    ];

    public function __construct(
        private readonly CompanyRepositoryInterface $companies,
        private readonly InstanceSettingsRepositoryInterface $instanceSettings,
        private readonly SettingsRepositoryInterface $companySettings,
    ) {}

    public function status(): JsonResponse
    {
        return $this->success([
            'setup_completed' => $this->isSetupCompleted(),
            'has_company' => Company::query()->exists(),
            'has_instance_admin' => User::query()->withoutGlobalScope('company')->whereNull('company_id')->exists(),
        ], 'setup.status');
    }

    public function complete(CompleteSetupRequest $request): JsonResponse
    {
        if ($this->isSetupCompleted()) {
            return response()->json(['success' => false, 'message' => 'setup.already_completed'], 409);
        }

        $validated = $request->validated();
        $companyData = $validated['company'] ?? [];
        $adminData = $validated['admin'] ?? [];
        $company = $this->firstOrCreateCompany($companyData);
        $admin = $this->firstOrCreateInstanceAdmin($adminData);

        $this->seedCompanySettings((int) $company->getKey());
        $this->instanceSettings->setMany([
            'setup_completed' => true,
            'setup_completed_at' => now()->toIso8601String(),
            'setup_completed_by' => $admin->getKey(),
            'default_locale' => $adminData['language_preference']
                ?? $companyData['language_default']
                ?? 'id',
            'mandatory_modules_installed' => true,
            'installed_mandatory_modules' => self::MANDATORY_MODULES,
        ]);

        return $this->success([
            'company_id' => $company->getKey(),
            'company_slug' => $company->getAttribute('slug'),
            'instance_admin_id' => $admin->getKey(),
            'mandatory_modules' => self::MANDATORY_MODULES,
        ], 'setup.completed', 201);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function firstOrCreateCompany(array $data): Company
    {
        $existing = Company::query()->orderBy('id')->first();

        if ($existing instanceof Company) {
            return $existing;
        }

        return $this->companies->create([
            'name' => $data['name'],
            'legal_name' => $data['legal_name'],
            'slug' => $data['slug'] ?? null,
            'timezone' => $data['timezone'] ?? 'Asia/Jakarta',
            'date_format' => $data['date_format'] ?? 'DD/MM/YYYY',
            'language_default' => $data['language_default'] ?? 'id',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function firstOrCreateInstanceAdmin(array $data): User
    {
        $existing = User::query()->withoutGlobalScope('company')->whereNull('company_id')->orderBy('id')->first();

        if ($existing instanceof User) {
            return $existing;
        }

        return User::query()->withoutGlobalScope('company')->create([
            'company_id' => null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'language_preference' => $data['language_preference'] ?? 'id',
            'force_password_reset' => false,
        ]);
    }

    private function seedCompanySettings(int $companyId): void
    {
        $this->companySettings->setManyForCompany([
            'password_min_length' => 8,
            'password_requires_uppercase' => true,
            'password_requires_number' => true,
            'password_requires_symbol' => false,
            'max_login_attempts' => 5,
            'lockout_duration_minutes' => 15,
            'session_lifetime_minutes' => 120,
            'retention_employee_years' => 5,
            'retention_candidate_years' => 1,
            'retention_audit_years' => 2,
            'smtp_port' => 587,
            'storage_disk' => 'documents',
            'storage_max_upload_mb' => 10,
        ], $companyId);
    }

    private function isSetupCompleted(): bool
    {
        return $this->instanceSettings->get('setup_completed') === true;
    }

    private function success(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }
}
