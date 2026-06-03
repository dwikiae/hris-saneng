<?php

use App\Http\Controllers\Api\V1\Archive\ArchiveController;
use App\Http\Controllers\Api\V1\Audit\AuditController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\UserController;
use App\Http\Controllers\Api\V1\Company\CompanyController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\Employee\EmployeeController;
use App\Http\Controllers\Api\V1\Employee\EmployeeDocumentController;
use App\Http\Controllers\Api\V1\Employee\EmployeePhotoController;
use App\Http\Controllers\Api\V1\MasterData\BankController;
use App\Http\Controllers\Api\V1\MasterData\BloodTypeController;
use App\Http\Controllers\Api\V1\MasterData\DepartmentController;
use App\Http\Controllers\Api\V1\MasterData\DocumentTypeController;
use App\Http\Controllers\Api\V1\MasterData\EducationLevelController;
use App\Http\Controllers\Api\V1\MasterData\EmploymentTypeController;
use App\Http\Controllers\Api\V1\MasterData\MaritalStatusController;
use App\Http\Controllers\Api\V1\MasterData\PositionController;
use App\Http\Controllers\Api\V1\MasterData\ReligionController;
use App\Http\Controllers\Api\V1\Notification\NotificationController;
use App\Http\Controllers\Api\V1\Public\PublicApplicationController;
use App\Http\Controllers\Api\V1\Public\PublicInterviewController;
use App\Http\Controllers\Api\V1\Public\PublicJobController;
use App\Http\Controllers\Api\V1\Public\PublicPemberkasanController;
use App\Http\Controllers\Api\V1\Public\PublicQuizController;
use App\Http\Controllers\Api\V1\Rbac\PermissionController;
use App\Http\Controllers\Api\V1\Rbac\RoleController;
use App\Http\Controllers\Api\V1\Recruitment\ApplicantController;
use App\Http\Controllers\Api\V1\Recruitment\BlacklistController;
use App\Http\Controllers\Api\V1\Recruitment\JobPostingController;
use App\Http\Controllers\Api\V1\Recruitment\TestController;
use App\Http\Controllers\Api\V1\Settings\SettingsController;
use App\Http\Controllers\Api\V1\Setup\SetupController;
use App\Http\Middleware\ForcePasswordReset;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::post('logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('change-password', [LoginController::class, 'changePassword'])->middleware('auth:sanctum');
    Route::get('me', [LoginController::class, 'me'])->middleware(['auth:sanctum', ForcePasswordReset::class]);
});

Route::prefix('v1/public')->group(function () {
    Route::get('jobs', [PublicJobController::class, 'index']);
    Route::post('applications', [PublicApplicationController::class, 'submit'])->middleware('throttle:5,10');
    Route::get('quiz/{token}', [PublicQuizController::class, 'show']);
    Route::post('quiz/{token}/submit', [PublicQuizController::class, 'submit']);
    Route::get('interview/{token}', [PublicInterviewController::class, 'show']);
    Route::post('interview/{token}/confirm', [PublicInterviewController::class, 'confirm']);
    Route::get('pemberkasan/{token}', [PublicPemberkasanController::class, 'show']);
    Route::post('pemberkasan/{token}/upload', [PublicPemberkasanController::class, 'upload']);
});

Route::prefix('v1/setup')->group(function () {
    Route::get('status', [SetupController::class, 'status']);
    Route::post('complete', [SetupController::class, 'complete']);
});

Route::prefix('v1')->middleware(['auth:sanctum', 'ip.whitelist'])->group(function () {

    Route::prefix('companies')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::post('/', [CompanyController::class, 'store']);
        Route::get('{company}', [CompanyController::class, 'show']);
        Route::put('{company}', [CompanyController::class, 'update']);
        Route::post('{company}/users', [CompanyController::class, 'storeUser']);
    });

    Route::get('settings/instance', [SettingsController::class, 'instanceIndex']);
    Route::put('settings/instance', [SettingsController::class, 'instanceUpdate']);
    Route::patch('users/me/preferences', [LoginController::class, 'updatePreferences']);

    Route::middleware('company.resolve')->group(function () {
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);

        Route::prefix('job-postings')->group(function () {
            Route::get('/', [JobPostingController::class, 'index']);
            Route::post('/', [JobPostingController::class, 'store']);
            Route::get('{id}', [JobPostingController::class, 'show']);
            Route::put('{id}', [JobPostingController::class, 'update']);
            Route::post('{id}/publish', [JobPostingController::class, 'publish']);
            Route::post('{id}/unpublish', [JobPostingController::class, 'unpublish']);
            Route::post('{id}/archive', [JobPostingController::class, 'archive']);
        });

        Route::prefix('applicants')->group(function () {
            Route::get('/', [ApplicantController::class, 'index']);
            Route::get('{id}', [ApplicantController::class, 'show']);
            Route::post('{id}/advance-tes-tulis', [ApplicantController::class, 'advanceToTesTulis']);
            Route::post('{id}/resend-quiz', [ApplicantController::class, 'resendQuizLink']);
            Route::post('{id}/schedule-interview', [ApplicantController::class, 'scheduleInterview']);
            Route::post('{id}/advance-stage', [ApplicantController::class, 'advanceStage']);
            Route::post('{id}/reject', [ApplicantController::class, 'reject']);
            Route::post('{id}/restore', [ApplicantController::class, 'restore']);
            Route::post('{id}/hire', [ApplicantController::class, 'hire']);
            Route::post('{id}/resend-pemberkasan', [ApplicantController::class, 'resendPemberkasanLink']);
            Route::post('{id}/blacklist', [ApplicantController::class, 'blacklist']);
            Route::post('{id}/unblacklist', [ApplicantController::class, 'unblacklist']);
            Route::post('{id}/notes', [ApplicantController::class, 'addNote']);
            Route::get('{id}/notes', [ApplicantController::class, 'getNotes']);
            Route::post('{id}/stages/{stage}/attachments', [ApplicantController::class, 'uploadAttachment']);
        });

        Route::prefix('recruitment')->group(function () {
            Route::get('blacklist', [BlacklistController::class, 'index']);
            Route::prefix('tests')->group(function () {
                Route::get('/', [TestController::class, 'index']);
                Route::post('/', [TestController::class, 'store']);
                Route::get('{id}', [TestController::class, 'show']);
                Route::put('{id}', [TestController::class, 'update']);
                Route::post('{id}/questions', [TestController::class, 'addQuestion']);
                Route::put('{id}/questions/{qid}', [TestController::class, 'updateQuestion']);
                Route::delete('{id}/questions/{qid}', [TestController::class, 'deleteQuestion']);
                Route::post('{id}/archive', [TestController::class, 'archive']);
            });
        });

        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('{id}', [UserController::class, 'show']);
            Route::put('{id}', [UserController::class, 'update']);
            Route::post('{id}/archive', [UserController::class, 'archive']);
            Route::post('{id}/restore', [UserController::class, 'restore']);
            Route::post('{id}/roles', [UserController::class, 'syncRoles']);
        });

        Route::prefix('employees')->group(function () {
            Route::get('/', [EmployeeController::class, 'index']);
            Route::post('/', [EmployeeController::class, 'store']);
            Route::get('{id}', [EmployeeController::class, 'show']);
            Route::put('{id}', [EmployeeController::class, 'update']);
            Route::get('{id}/documents', [EmployeeDocumentController::class, 'index']);
            Route::post('{id}/documents', [EmployeeDocumentController::class, 'store']);
            Route::delete('{id}/documents/{docId}', [EmployeeDocumentController::class, 'archive']);
            Route::get('{id}/photo', [EmployeePhotoController::class, 'show']);
            Route::post('{id}/photo', [EmployeePhotoController::class, 'store']);
            Route::post('{id}/approve', [EmployeeController::class, 'approve']);
            Route::post('{id}/reject', [EmployeeController::class, 'reject']);
            Route::post('{id}/archive', [EmployeeController::class, 'archive']);
        });

        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::post('/', [RoleController::class, 'store']);
            Route::get('{id}', [RoleController::class, 'show']);
            Route::put('{id}', [RoleController::class, 'update']);
            Route::post('{id}/sync-permissions', [RoleController::class, 'syncPermissions']);
        });

        Route::get('permissions', [PermissionController::class, 'index']);
        Route::get('settings', [SettingsController::class, 'index']);
        Route::put('settings', [SettingsController::class, 'update']);
        Route::get('audit', [AuditController::class, 'index']);
        Route::get('archive', [ArchiveController::class, 'index']);
        Route::post('archive/{model}/{id}/restore', [ArchiveController::class, 'restore']);

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('read-all', [NotificationController::class, 'markAllRead']);
            Route::post('{notification}/read', [NotificationController::class, 'markRead']);
        });

        Route::prefix('master-data')->group(function () {

            Route::prefix('departments')->group(function () {
                Route::get('/', [DepartmentController::class, 'index']);
                Route::post('/', [DepartmentController::class, 'store']);
                Route::get('{id}', [DepartmentController::class, 'show']);
                Route::put('{id}', [DepartmentController::class, 'update']);
                Route::post('{id}/archive', [DepartmentController::class, 'archive']);
                Route::post('{id}/restore', [DepartmentController::class, 'restore']);
            });

            Route::prefix('positions')->group(function () {
                Route::get('/', [PositionController::class, 'index']);
                Route::post('/', [PositionController::class, 'store']);
                Route::get('{id}', [PositionController::class, 'show']);
                Route::put('{id}', [PositionController::class, 'update']);
                Route::post('{id}/archive', [PositionController::class, 'archive']);
                Route::post('{id}/restore', [PositionController::class, 'restore']);
            });

            Route::prefix('employment-types')->group(function () {
                Route::get('/', [EmploymentTypeController::class, 'index']);
                Route::post('/', [EmploymentTypeController::class, 'store']);
                Route::get('{id}', [EmploymentTypeController::class, 'show']);
                Route::put('{id}', [EmploymentTypeController::class, 'update']);
                Route::post('{id}/archive', [EmploymentTypeController::class, 'archive']);
                Route::post('{id}/restore', [EmploymentTypeController::class, 'restore']);
            });

            Route::prefix('education-levels')->group(function () {
                Route::get('/', [EducationLevelController::class, 'index']);
                Route::post('/', [EducationLevelController::class, 'store']);
                Route::get('{id}', [EducationLevelController::class, 'show']);
                Route::put('{id}', [EducationLevelController::class, 'update']);
                Route::post('{id}/archive', [EducationLevelController::class, 'archive']);
                Route::post('{id}/restore', [EducationLevelController::class, 'restore']);
            });

            Route::prefix('religions')->group(function () {
                Route::get('/', [ReligionController::class, 'index']);
                Route::post('/', [ReligionController::class, 'store']);
                Route::get('{id}', [ReligionController::class, 'show']);
                Route::put('{id}', [ReligionController::class, 'update']);
                Route::post('{id}/archive', [ReligionController::class, 'archive']);
                Route::post('{id}/restore', [ReligionController::class, 'restore']);
            });

            Route::prefix('marital-statuses')->group(function () {
                Route::get('/', [MaritalStatusController::class, 'index']);
                Route::post('/', [MaritalStatusController::class, 'store']);
                Route::get('{id}', [MaritalStatusController::class, 'show']);
                Route::put('{id}', [MaritalStatusController::class, 'update']);
                Route::post('{id}/archive', [MaritalStatusController::class, 'archive']);
                Route::post('{id}/restore', [MaritalStatusController::class, 'restore']);
            });

            Route::prefix('blood-types')->group(function () {
                Route::get('/', [BloodTypeController::class, 'index']);
                Route::post('/', [BloodTypeController::class, 'store']);
                Route::get('{id}', [BloodTypeController::class, 'show']);
                Route::put('{id}', [BloodTypeController::class, 'update']);
                Route::post('{id}/archive', [BloodTypeController::class, 'archive']);
                Route::post('{id}/restore', [BloodTypeController::class, 'restore']);
            });

            Route::prefix('banks')->group(function () {
                Route::get('/', [BankController::class, 'index']);
                Route::post('/', [BankController::class, 'store']);
                Route::get('{id}', [BankController::class, 'show']);
                Route::put('{id}', [BankController::class, 'update']);
                Route::post('{id}/archive', [BankController::class, 'archive']);
                Route::post('{id}/restore', [BankController::class, 'restore']);
            });

            Route::prefix('document-types')->group(function () {
                Route::get('/', [DocumentTypeController::class, 'index']);
                Route::post('/', [DocumentTypeController::class, 'store']);
                Route::get('{id}', [DocumentTypeController::class, 'show']);
                Route::put('{id}', [DocumentTypeController::class, 'update']);
                Route::post('{id}/archive', [DocumentTypeController::class, 'archive']);
                Route::post('{id}/restore', [DocumentTypeController::class, 'restore']);
            });

        });
    });

});
