<?php

namespace App\Modules\Karyawan\Repositories\Contracts;

interface EmployeeModuleSettingsRepositoryInterface
{
    /**
     * @return array<string, string>
     */
    public function valuesForCompany(int $companyId): array;

    /**
     * @param  array<string, mixed>  $values
     */
    public function sync(int $companyId, array $values): void;
}
