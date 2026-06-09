<?php

namespace App\Modules\Karyawan;

use App\Modules\Karyawan\Application\EmployeeLevelService;
use App\Modules\Karyawan\Application\BankMasterService;
use App\Modules\Karyawan\Application\ContractTypeService;
use App\Modules\Karyawan\Application\DepartmentMasterService;
use App\Modules\Karyawan\Application\DocumentTypeMasterService;
use App\Modules\Karyawan\Application\EducationLevelMasterService;
use App\Modules\Karyawan\Application\JobPositionService;
use App\Modules\Karyawan\Application\ReligionMasterService;
use App\Modules\Karyawan\Application\WorkLocationService;
use App\Models\Bank;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\EducationLevel;
use App\Models\Position;
use App\Models\Religion;
use App\Modules\Karyawan\Console\CountriesSyncCommand;
use App\Modules\Karyawan\Console\WilayahSyncCommand;
use App\Modules\Karyawan\Models\ContractType;
use App\Modules\Karyawan\Models\EmployeeLevel;
use App\Modules\Karyawan\Models\WorkLocation;
use App\Modules\Karyawan\Repositories\Contracts\EmployeeMasterRepositoryInterface;
use App\Modules\Karyawan\Repositories\Contracts\EmployeeModuleSettingsRepositoryInterface;
use App\Modules\Karyawan\Repositories\Contracts\WilayahRepositoryInterface;
use App\Modules\Karyawan\Repositories\Eloquent\EmployeeMasterRepository;
use App\Modules\Karyawan\Repositories\Eloquent\EmployeeModuleSettingsRepository;
use App\Modules\Karyawan\Repositories\Eloquent\WilayahRepository;
use Illuminate\Support\ServiceProvider;

class KaryawanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(WorkLocationService::class)
            ->needs(EmployeeMasterRepositoryInterface::class)
            ->give(fn (): EmployeeMasterRepository => new EmployeeMasterRepository(WorkLocation::class));

        $this->app->when(EmployeeLevelService::class)
            ->needs(EmployeeMasterRepositoryInterface::class)
            ->give(fn (): EmployeeMasterRepository => new EmployeeMasterRepository(EmployeeLevel::class));

        $this->app->when(DepartmentMasterService::class)
            ->needs(EmployeeMasterRepositoryInterface::class)
            ->give(fn (): EmployeeMasterRepository => new EmployeeMasterRepository(Department::class));

        $this->app->when(JobPositionService::class)
            ->needs(EmployeeMasterRepositoryInterface::class)
            ->give(fn (): EmployeeMasterRepository => new EmployeeMasterRepository(Position::class));

        $this->app->when(ContractTypeService::class)
            ->needs(EmployeeMasterRepositoryInterface::class)
            ->give(fn (): EmployeeMasterRepository => new EmployeeMasterRepository(ContractType::class));

        $this->app->when(ReligionMasterService::class)
            ->needs(EmployeeMasterRepositoryInterface::class)
            ->give(fn (): EmployeeMasterRepository => new EmployeeMasterRepository(Religion::class));

        $this->app->when(BankMasterService::class)
            ->needs(EmployeeMasterRepositoryInterface::class)
            ->give(fn (): EmployeeMasterRepository => new EmployeeMasterRepository(Bank::class));

        $this->app->when(DocumentTypeMasterService::class)
            ->needs(EmployeeMasterRepositoryInterface::class)
            ->give(fn (): EmployeeMasterRepository => new EmployeeMasterRepository(DocumentType::class));

        $this->app->when(EducationLevelMasterService::class)
            ->needs(EmployeeMasterRepositoryInterface::class)
            ->give(fn (): EmployeeMasterRepository => new EmployeeMasterRepository(EducationLevel::class));

        $this->app->bind(EmployeeModuleSettingsRepositoryInterface::class, EmployeeModuleSettingsRepository::class);
        $this->app->bind(WilayahRepositoryInterface::class, WilayahRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                WilayahSyncCommand::class,
                CountriesSyncCommand::class,
            ]);
        }
    }
}
