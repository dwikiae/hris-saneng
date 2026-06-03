<?php

namespace App\Providers;

use App\Core\Company\Application\CompanyContext;
use App\Core\FileStorage\Domain\StorageAdapterInterface;
use App\Core\FileStorage\Infrastructure\MinIOStorageAdapter;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\DashboardStatsRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\InstanceCompanyRepositoryInterface;
use App\Repositories\Contracts\InstancePermissionRepositoryInterface;
use App\Repositories\Contracts\InstanceRoleRepositoryInterface;
use App\Repositories\Contracts\InstanceSettingsRepositoryInterface;
use App\Repositories\Contracts\InstanceUserRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantBlacklistRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantDocumentRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantNoteRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantRepositoryInterface;
use App\Repositories\Contracts\Recruitment\ApplicantStageAttachmentRepositoryInterface;
use App\Repositories\Contracts\Recruitment\InterviewScheduleRepositoryInterface;
use App\Repositories\Contracts\Recruitment\JobPostingRepositoryInterface;
use App\Repositories\Contracts\Recruitment\QuizSessionRepositoryInterface;
use App\Repositories\Contracts\Recruitment\TestRepositoryInterface;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use App\Repositories\Eloquent\CompanyRepository;
use App\Repositories\Eloquent\DashboardStatsRepository;
use App\Repositories\Eloquent\EmployeeRepository;
use App\Repositories\Eloquent\InstanceCompanyRepository;
use App\Repositories\Eloquent\InstancePermissionRepository;
use App\Repositories\Eloquent\InstanceRoleRepository;
use App\Repositories\Eloquent\InstanceSettingsRepository;
use App\Repositories\Eloquent\InstanceUserRepository;
use App\Repositories\Eloquent\NotificationRepository;
use App\Repositories\Eloquent\Recruitment\ApplicantBlacklistRepository;
use App\Repositories\Eloquent\Recruitment\ApplicantDocumentRepository;
use App\Repositories\Eloquent\Recruitment\ApplicantNoteRepository;
use App\Repositories\Eloquent\Recruitment\ApplicantRepository;
use App\Repositories\Eloquent\Recruitment\ApplicantStageAttachmentRepository;
use App\Repositories\Eloquent\Recruitment\InterviewScheduleRepository;
use App\Repositories\Eloquent\Recruitment\JobPostingRepository;
use App\Repositories\Eloquent\Recruitment\QuizSessionRepository;
use App\Repositories\Eloquent\Recruitment\TestRepository;
use App\Repositories\Eloquent\SettingsRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CompanyContext::class, fn (): CompanyContext => new CompanyContext);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(StorageAdapterInterface::class, MinIOStorageAdapter::class);
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(InstanceCompanyRepositoryInterface::class, InstanceCompanyRepository::class);
        $this->app->bind(InstanceUserRepositoryInterface::class, InstanceUserRepository::class);
        $this->app->bind(InstanceRoleRepositoryInterface::class, InstanceRoleRepository::class);
        $this->app->bind(InstancePermissionRepositoryInterface::class, InstancePermissionRepository::class);
        $this->app->bind(DashboardStatsRepositoryInterface::class, DashboardStatsRepository::class);
        $this->app->bind(InstanceSettingsRepositoryInterface::class, InstanceSettingsRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
        $this->app->bind(JobPostingRepositoryInterface::class, JobPostingRepository::class);
        $this->app->bind(ApplicantRepositoryInterface::class, ApplicantRepository::class);
        $this->app->bind(TestRepositoryInterface::class, TestRepository::class);
        $this->app->bind(QuizSessionRepositoryInterface::class, QuizSessionRepository::class);
        $this->app->bind(InterviewScheduleRepositoryInterface::class, InterviewScheduleRepository::class);
        $this->app->bind(ApplicantDocumentRepositoryInterface::class, ApplicantDocumentRepository::class);
        $this->app->bind(ApplicantNoteRepositoryInterface::class, ApplicantNoteRepository::class);
        $this->app->bind(ApplicantBlacklistRepositoryInterface::class, ApplicantBlacklistRepository::class);
        $this->app->bind(ApplicantStageAttachmentRepositoryInterface::class, ApplicantStageAttachmentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
