<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ContractType;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->withoutGlobalScope('company')->firstOrFail();

        foreach ($this->contractTypes() as $code => $row) {
            ContractType::query()->withoutGlobalScope('company')->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'code' => $code,
                ],
                [
                    'name' => $row['name'],
                    'requires_end_date' => $row['requires_end_date'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function contractTypes(): array
    {
        return [
            'PKWT' => ['name' => 'PKWT', 'requires_end_date' => true],
            'TETAP' => ['name' => 'Karyawan Tetap', 'requires_end_date' => false],
            'OUTSOURCING' => ['name' => 'Outsourcing', 'requires_end_date' => true],
            'MAGANG' => ['name' => 'Magang', 'requires_end_date' => true],
        ];
    }
}
