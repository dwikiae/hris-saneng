<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\TerminationReason;
use Illuminate\Database\Seeder;

class TerminationReasonSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->withoutGlobalScope('company')->firstOrFail();

        foreach ($this->reasons() as $code => $name) {
            TerminationReason::query()->withoutGlobalScope('company')->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'code' => $code,
                ],
                [
                    'name' => $name,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function reasons(): array
    {
        return [
            'resign' => 'Resign',
            'contract_ended' => 'Kontrak Habis',
            'termination' => 'PHK',
            'retirement' => 'Pensiun',
            'deceased' => 'Meninggal',
            'transfer' => 'Transfer',
        ];
    }
}
