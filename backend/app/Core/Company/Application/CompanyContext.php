<?php

namespace App\Core\Company\Application;

use App\Models\Company;
use RuntimeException;

class CompanyContext
{
    private ?Company $company = null;

    public function set(Company $company): void
    {
        $this->company = $company;
    }

    public function clear(): void
    {
        $this->company = null;
    }

    public function hasCompany(): bool
    {
        return $this->company instanceof Company;
    }

    public function company(): Company
    {
        if (! $this->company instanceof Company) {
            throw new RuntimeException('company.context.required');
        }

        return $this->company;
    }

    public function companyId(): int
    {
        return (int) $this->company()->getKey();
    }
}
