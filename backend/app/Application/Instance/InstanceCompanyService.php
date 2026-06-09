<?php

namespace App\Application\Instance;

use App\Models\Company;
use App\Modules\Karyawan\Application\EmployeeDefaultMasterDataService;
use App\Repositories\Contracts\InstanceCompanyRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class InstanceCompanyService
{
    /**
     * @var array<string, string>
     */
    private array $coreFields = [
        'name' => 'name',
        'legal_name' => 'legal_name',
        'slug' => 'slug',
        'npwp' => 'npwp',
        'address' => 'address',
        'city' => 'city',
        'phone' => 'phone',
        'email' => 'email',
        'logo_path' => 'logo_path',
        'website' => 'website',
        'timezone' => 'timezone',
        'date_format' => 'date_format',
        'language_default' => 'language_default',
    ];

    /**
     * @var array<string, string>
     */
    private array $settingFields = [
        'tagline' => 'tagline',
        'companyType' => 'company_type',
        'industry' => 'industry',
        'foundedDate' => 'founded_date',
        'province' => 'province',
        'postalCode' => 'postal_code',
        'hrPicName' => 'hr_pic_name',
        'hrPicPhone' => 'hr_pic_phone',
        'hrPicEmail' => 'hr_pic_email',
        'nibOrSiup' => 'nib_or_siup',
        'bpjsKetenagakerjaan' => 'bpjs_ketenagakerjaan',
        'bpjsKesehatan' => 'bpjs_kesehatan',
        'wlkpNumber' => 'wlkp_number',
        'directorName' => 'director_name',
        'smtpHost' => 'smtp_host',
        'smtpPort' => 'smtp_port',
        'smtpUsername' => 'smtp_username',
        'smtpPasswordMasked' => 'smtp_password_masked',
        'smtpFromName' => 'smtp_from_name',
        'smtpFromEmail' => 'smtp_from_email',
        'loginLockoutAttempts' => 'login_lockout_attempts',
        'loginLockoutMinutes' => 'login_lockout_minutes',
        'sessionDurationHours' => 'session_duration_hours',
        'activeModuleCodes' => 'active_module_codes',
    ];

    public function __construct(
        private readonly InstanceCompanyRepositoryInterface $companies,
        private readonly PlatformAdministratorAssignmentService $platformAdministrators,
        private readonly EmployeeDefaultMasterDataService $employeeDefaults,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->companies->paginate($filters, $perPage);
    }

    public function find(int $id): ?Company
    {
        return $this->companies->find($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload): Company
    {
        $company = $this->companies->create($this->corePayload($payload));
        $this->companies->syncSettings($company, $this->settingsPayload($payload));
        $this->platformAdministrators->assignAllToCompany($company);
        $this->employeeDefaults->seedForCompany((int) $company->getKey());

        return $this->companies->find((int) $company->getKey()) ?? $company;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(int $id, array $payload): ?Company
    {
        $company = $this->companies->find($id);

        if (! $company instanceof Company) {
            return null;
        }

        $updated = $this->companies->update($company, $this->corePayload($payload));
        $this->companies->syncSettings($updated, $this->settingsPayload($payload));

        return $this->companies->find((int) $updated->getKey()) ?? $updated;
    }

    public function archive(int $id): bool
    {
        $company = $this->companies->find($id);

        if (! $company instanceof Company) {
            return false;
        }

        $this->companies->archive($company);
        activity()
            ->useLog('companies')
            ->performedOn($company)
            ->event('archived')
            ->log('companies.archived');

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function extendedSettings(Company $company): array
    {
        return $this->companies->settingsFor($company);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function corePayload(array $payload): array
    {
        $data = [];

        foreach ($this->coreFields as $source => $target) {
            if (array_key_exists($source, $payload)) {
                $data[$target] = $payload[$source];
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function settingsPayload(array $payload): array
    {
        $settings = [];

        foreach ($this->settingFields as $source => $target) {
            if (array_key_exists($source, $payload)) {
                $settings[$target] = $payload[$source];
            }
        }

        return $settings;
    }
}
