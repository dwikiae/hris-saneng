<?php

namespace App\Modules\Karyawan;

use App\Modules\Karyawan\Application\EmployeeLevelService;
use App\Modules\Karyawan\Application\WorkLocationService;
use App\Modules\Karyawan\Console\CountriesSyncCommand;
use App\Modules\Karyawan\Console\WilayahSyncCommand;
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
