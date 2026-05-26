<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\OffboardingTemplate;
use App\Models\TerminationReason;
use Illuminate\Database\Seeder;

class OffboardingTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TerminationReasonSeeder::class);

        $company = Company::query()->withoutGlobalScope('company')->firstOrFail();

        foreach ($this->templates() as $reasonCode => $items) {
            $reason = TerminationReason::query()
                ->withoutGlobalScope('company')
                ->where('company_id', $company->getKey())
                ->where('code', $reasonCode)
                ->firstOrFail();

            $template = OffboardingTemplate::query()->withoutGlobalScope('company')->updateOrCreate(
                [
                    'company_id' => $company->getKey(),
                    'termination_reason_id' => $reason->getKey(),
                ],
                [
                    'name' => $reason->name.' Checklist',
                    'is_active' => true,
                ]
            );

            foreach ($items as $index => $item) {
                $template->items()->withoutGlobalScope('company')->updateOrCreate(
                    [
                        'company_id' => $company->getKey(),
                        'item_name' => $item['item_name'],
                    ],
                    [
                        'department_responsible' => $item['department_responsible'],
                        'is_mandatory' => $item['is_mandatory'],
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function templates(): array
    {
        return [
            'resign' => [
                $this->item('Kembalikan laptop / perangkat kerja', 'IT / Warehouse'),
                $this->item('Kembalikan ID card & akses gedung', 'Security / IT'),
                $this->item('Cabut semua akses sistem', 'IT'),
                $this->item('Exit interview dengan HRD', 'HRD'),
                $this->item('Serahkan surat pengalaman kerja', 'HRD'),
                $this->item('Clearance Finance', 'Finance'),
                $this->item('Serahkan dokumen BPJS', 'HRD', false),
            ],
            'contract_ended' => [
                $this->item('Kembalikan perangkat kerja', 'IT / Warehouse'),
                $this->item('Kembalikan ID card', 'Security'),
                $this->item('Cabut akses sistem', 'IT'),
                $this->item('Clearance Finance', 'Finance'),
                $this->item('Serahkan dokumen BPJS', 'HRD', false),
            ],
            'termination' => [
                $this->item('Kembalikan semua aset perusahaan', 'IT / Warehouse'),
                $this->item('Cabut semua akses sistem segera', 'IT'),
                $this->item('Serahkan dokumen PHK yang telah ditandatangani', 'HRD / Legal'),
                $this->item('Clearance Finance', 'Finance'),
            ],
            'retirement' => [
                $this->item('Serahkan penghargaan masa kerja', 'HRD'),
                $this->item('Proses dokumen pensiun BPJS Ketenagakerjaan', 'HRD'),
                $this->item('Cabut akses sistem', 'IT'),
                $this->item('Exit interview', 'HRD', false),
            ],
            'deceased' => [
                $this->item('Proses dokumen ahli waris', 'HRD / Legal'),
                $this->item('Koordinasi BPJS Ketenagakerjaan', 'HRD'),
                $this->item('Cabut akses sistem', 'IT'),
                $this->item('Arsip seluruh dokumen kepegawaian', 'HRD'),
            ],
            'transfer' => [
                $this->item('Validasi surat transfer', 'HRD'),
                $this->item('Update akses sistem sesuai unit baru', 'IT'),
                $this->item('Serah terima aset antar lokasi', 'Warehouse'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(string $name, string $department, bool $mandatory = true): array
    {
        return [
            'item_name' => $name,
            'department_responsible' => $department,
            'is_mandatory' => $mandatory,
        ];
    }
}
