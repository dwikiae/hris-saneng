<?php

namespace App\Providers;

use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\EmployeeChatterRepositoryInterface;
use App\Repositories\Contracts\EmployeeOffboardingRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
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
use App\Repositories\Eloquent\EloquentContractRepository;
use App\Repositories\Eloquent\EloquentEmployeeChatterRepository;
use App\Repositories\Eloquent\EloquentEmployeeRepository;
use App\Repositories\Eloquent\EloquentEmployeeOffboardingRepository;
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
        $this->app->bind(EmployeeRepositoryInterface::class, EloquentEmployeeRepository::class);
        $this->app->bind(ContractRepositoryInterface::class, EloquentContractRepository::class);
        $this->app->bind(EmployeeOffboardingRepositoryInterface::class, EloquentEmployeeOffboardingRepository::class);
        $this->app->bind(EmployeeChatterRepositoryInterface::class, EloquentEmployeeChatterRepository::class);
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
