<?php

use App\Http\Controllers\Api\V1\MasterData\BankController;
use App\Http\Controllers\Api\V1\MasterData\BloodTypeController;
use App\Http\Controllers\Api\V1\MasterData\DepartmentController;
use App\Http\Controllers\Api\V1\MasterData\DocumentTypeController;
use App\Http\Controllers\Api\V1\MasterData\EducationLevelController;
use App\Http\Controllers\Api\V1\MasterData\EmploymentTypeController;
use App\Http\Controllers\Api\V1\MasterData\MaritalStatusController;
use App\Http\Controllers\Api\V1\MasterData\PositionController;
use App\Http\Controllers\Api\V1\MasterData\ReligionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

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
