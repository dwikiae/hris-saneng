<?php

namespace App\Application\Instance;

use App\Core\FileStorage\Domain\StorageAdapterInterface;
use App\Core\ModuleRegistry\Domain\ModuleDefinition;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InstanceModuleExportService
{
    public function __construct(
        private readonly StorageAdapterInterface $storage,
        private readonly InstanceAuditExportWriter $writer,
    ) {}

    /**
     * @return array{exportId: string, module: string, companies: array<int, array<string, mixed>>, recordCount: int}
     */
    public function export(ModuleDefinition $module): array
    {
        $exportId = Str::uuid()->toString();
        $date = now()->toDateString();
        $companies = [];
        $total = 0;

        Company::query()->orderBy('id')->get()->each(function (Company $company) use ($module, $date, &$companies, &$total): void {
            $recordCount = $this->recordCount($module->code, (int) $company->getKey());
            $path = "exports/{$company->getKey()}/{$date}/{$module->code}.xlsx";

            $this->storage->putPrivate($path, $this->writer->xlsx([
                ['Module', 'Company ID', 'Company Name', 'Record Count', 'Exported At'],
                [$module->code, (string) $company->getKey(), (string) $company->getAttribute('name'), (string) $recordCount, now()->toISOString()],
            ], 'Module Export'));

            $companies[] = [
                'companyId' => $company->getKey(),
                'companyName' => $company->getAttribute('name'),
                'path' => $path,
                'downloadUrl' => $this->storage->privateSignedUrl($path, 60),
                'recordCount' => $recordCount,
            ];
            $total += $recordCount;
        });

        return [
            'exportId' => $exportId,
            'module' => $module->code,
            'companies' => $companies,
            'recordCount' => $total,
        ];
    }

    private function recordCount(string $code, int $companyId): int
    {
        return collect($this->tablesForModule($code))
            ->filter(fn (string $table): bool => Schema::hasTable($table) && Schema::hasColumn($table, 'company_id'))
            ->sum(fn (string $table): int => DB::table($table)->where('company_id', $companyId)->count());
    }

    /**
     * @return array<int, string>
     */
    private function tablesForModule(string $code): array
    {
        return match ($code) {
            'karyawan' => ['employees'],
            'recruitment' => ['job_postings', 'applicants'],
            default => [],
        };
    }
}
