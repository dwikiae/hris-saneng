<?php

namespace App\Providers;

use App\Application\MasterData\BankService;
use App\Application\MasterData\BloodTypeService;
use App\Application\MasterData\DepartmentService;
use App\Application\MasterData\DocumentTypeService;
use App\Application\MasterData\EducationLevelService;
use App\Application\MasterData\EmploymentTypeService;
use App\Application\MasterData\MaritalStatusService;
use App\Application\MasterData\PositionService;
use App\Application\MasterData\ReligionService;
use App\Repositories\Contracts\MasterDataRepositoryInterface;
use App\Repositories\Eloquent\BankRepository;
use App\Repositories\Eloquent\BloodTypeRepository;
use App\Repositories\Eloquent\DepartmentRepository;
use App\Repositories\Eloquent\DocumentTypeRepository;
use App\Repositories\Eloquent\EducationLevelRepository;
use App\Repositories\Eloquent\EmploymentTypeRepository;
use App\Repositories\Eloquent\MaritalStatusRepository;
use App\Repositories\Eloquent\PositionRepository;
use App\Repositories\Eloquent\ReligionRepository;
use Illuminate\Support\ServiceProvider;

class MasterDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(DepartmentService::class)
            ->needs(MasterDataRepositoryInterface::class)
            ->give(DepartmentRepository::class);

        $this->app->when(PositionService::class)
            ->needs(MasterDataRepositoryInterface::class)
            ->give(PositionRepository::class);

        $this->app->when(EmploymentTypeService::class)
            ->needs(MasterDataRepositoryInterface::class)
            ->give(EmploymentTypeRepository::class);

        $this->app->when(EducationLevelService::class)
            ->needs(MasterDataRepositoryInterface::class)
            ->give(EducationLevelRepository::class);

        $this->app->when(ReligionService::class)
            ->needs(MasterDataRepositoryInterface::class)
            ->give(ReligionRepository::class);

        $this->app->when(MaritalStatusService::class)
            ->needs(MasterDataRepositoryInterface::class)
            ->give(MaritalStatusRepository::class);

        $this->app->when(BloodTypeService::class)
            ->needs(MasterDataRepositoryInterface::class)
            ->give(BloodTypeRepository::class);

        $this->app->when(BankService::class)
            ->needs(MasterDataRepositoryInterface::class)
            ->give(BankRepository::class);

        $this->app->when(DocumentTypeService::class)
            ->needs(MasterDataRepositoryInterface::class)
            ->give(DocumentTypeRepository::class);
    }
}
