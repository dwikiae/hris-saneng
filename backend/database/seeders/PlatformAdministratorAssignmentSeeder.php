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

        if (! $admin instanceof User) {
            return;
        }

        Company::query()
            ->orderBy('id')
            ->each(fn (Company $company): void => app(PlatformAdministratorAssignmentService::class)
                ->assignUserToCompany($admin, $company));
    }
}
