<?php

namespace Database\Seeders;

use App\Application\Instance\PlatformAdministratorAssignmentService;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlatformAdministratorAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::withoutCompanyScope()
            ->where('email', 'admin@saneng.co.id')
            ->first();
        $company = Company::query()
            ->where('name', 'PT Saneng')
            ->first();

        if (! $admin instanceof User || ! $company instanceof Company) {
            return;
        }

        app(PlatformAdministratorAssignmentService::class)
            ->assignUserToCompany($admin, $company);
    }
}
