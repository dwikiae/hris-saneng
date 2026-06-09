<?php

namespace App\Modules\Karyawan\Application;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

class EmployeeDefaultMasterDataService
{
    public function seedForAllCompanies(): void
    {
        Company::query()
            ->withoutGlobalScope('company')
            ->orderBy('id')
            ->each(fn (Company $company) => $this->seedForCompany((int) $company->getKey()));
    }

    public function seedForCompany(int $companyId): void
    {
        $this->seedRows('religions', $companyId, $this->religions());
        $this->seedRows('banks', $companyId, $this->banks());
        $this->seedRows('contract_types', $companyId, $this->contractTypes());
        $this->seedRows('document_types', $companyId, $this->documentTypes());
        $this->seedRows('education_levels', $companyId, $this->educationLevels());
        $this->seedRows('employment_types', $companyId, $this->legacyEmploymentTypes());
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function seedRows(string $table, int $companyId, array $rows): void
    {
        $now = now();

        foreach ($rows as $row) {
            $identity = ['company_id' => $companyId, 'code' => $row['code']];
            $payload = array_merge($row, [
                'company_id' => $companyId,
                'is_active' => $row['is_active'] ?? true,
                'updated_at' => $now,
            ]);

            if (DB::table($table)->where($identity)->exists()) {
                DB::table($table)->where($identity)->update($payload);
                continue;
            }

            DB::table($table)->insert(array_merge($payload, ['created_at' => $now]));
        }
    }

    private function religions(): array
    {
        return [
            ['code' => 'ISLAM', 'name' => 'Islam'],
            ['code' => 'KRISTEN_PROTESTAN', 'name' => 'Kristen Protestan'],
            ['code' => 'KATOLIK', 'name' => 'Katolik'],
            ['code' => 'HINDU', 'name' => 'Hindu'],
            ['code' => 'BUDDHA', 'name' => 'Buddha'],
            ['code' => 'KONGHUCU', 'name' => 'Konghucu'],
        ];
    }

    private function banks(): array
    {
        return [
            ['code' => 'BCA', 'name' => 'BCA'],
            ['code' => 'BNI', 'name' => 'BNI'],
            ['code' => 'BRI', 'name' => 'BRI'],
            ['code' => 'MANDIRI', 'name' => 'Mandiri'],
            ['code' => 'BSI', 'name' => 'BSI'],
            ['code' => 'CIMB_NIAGA', 'name' => 'CIMB Niaga'],
            ['code' => 'DANAMON', 'name' => 'Danamon'],
            ['code' => 'PERMATA', 'name' => 'Permata'],
            ['code' => 'BTN', 'name' => 'BTN'],
            ['code' => 'MAYBANK', 'name' => 'Maybank'],
        ];
    }

    private function contractTypes(): array
    {
        return [
            ['code' => 'PKWT', 'name' => 'PKWT', 'type' => 'pkwt', 'max_duration_months' => 24],
            ['code' => 'PKWTT', 'name' => 'PKWTT', 'type' => 'pkwtt', 'max_duration_months' => null],
        ];
    }

    private function documentTypes(): array
    {
        return [
            ['code' => 'KTP', 'name' => 'KTP', 'is_mandatory' => true],
            ['code' => 'NPWP', 'name' => 'NPWP', 'is_mandatory' => false],
            ['code' => 'IJAZAH', 'name' => 'Ijazah', 'is_mandatory' => false],
            ['code' => 'SERTIFIKAT', 'name' => 'Sertifikat', 'is_mandatory' => false],
            ['code' => 'KONTRAK_KERJA', 'name' => 'Kontrak Kerja', 'is_mandatory' => false],
            ['code' => 'FOTO', 'name' => 'Foto', 'is_mandatory' => false],
        ];
    }

    private function educationLevels(): array
    {
        return [
            ['code' => 'SD', 'name' => 'SD', 'order' => 1],
            ['code' => 'SMP', 'name' => 'SMP', 'order' => 2],
            ['code' => 'SMA_SMK', 'name' => 'SMA/SMK', 'order' => 3],
            ['code' => 'D1', 'name' => 'D1', 'order' => 4],
            ['code' => 'D2', 'name' => 'D2', 'order' => 5],
            ['code' => 'D3', 'name' => 'D3', 'order' => 6],
            ['code' => 'D4_S1', 'name' => 'D4/S1', 'order' => 7],
            ['code' => 'S2', 'name' => 'S2', 'order' => 8],
            ['code' => 'S3', 'name' => 'S3', 'order' => 9],
        ];
    }

    private function legacyEmploymentTypes(): array
    {
        return [
            ['code' => 'PKWT', 'name' => 'PKWT'],
            ['code' => 'PKWTT', 'name' => 'PKWTT'],
        ];
    }
}
