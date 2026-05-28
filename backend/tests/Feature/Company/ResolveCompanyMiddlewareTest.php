<?php

use App\Core\Company\Application\CompanyContext;
use App\Http\Middleware\ResolveCompany;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(ResolveCompany::class)
        ->get('/testing/company-context/{company?}', function (CompanyContext $companyContext) {
            return response()->json([
                'company_id' => $companyContext->companyId(),
                'department_count' => Department::query()->count(),
                'request_company_id' => request()->attributes->get('company_id'),
            ]);
        });
});

it('injects company context from the route parameter and scopes company models', function () {
    $firstCompany = Company::create(['name' => 'PT First', 'legal_name' => 'PT First']);
    $secondCompany = Company::create(['name' => 'PT Second', 'legal_name' => 'PT Second']);

    Department::create(['company_id' => $firstCompany->id, 'code' => 'HRD', 'name' => 'HRD', 'is_active' => true]);
    Department::create(['company_id' => $secondCompany->id, 'code' => 'FIN', 'name' => 'Finance', 'is_active' => true]);

    $response = $this->getJson('/testing/company-context/'.$firstCompany->id);

    $response
        ->assertOk()
        ->assertJson([
            'company_id' => $firstCompany->id,
            'department_count' => 1,
            'request_company_id' => $firstCompany->id,
        ]);
});

it('injects company context from the company header', function () {
    $company = Company::create(['name' => 'PT Header', 'legal_name' => 'PT Header']);

    $response = $this->withHeader('X-Company-Id', (string) $company->id)
        ->getJson('/testing/company-context');

    $response
        ->assertOk()
        ->assertJson([
            'company_id' => $company->id,
            'request_company_id' => $company->id,
        ]);
});

it('rejects company-scoped requests without company context', function () {
    $this->getJson('/testing/company-context')
        ->assertBadRequest()
        ->assertJson([
            'success' => false,
            'message' => 'company.context.required',
        ]);
});

it('rejects requests with an unknown company context', function () {
    $this->getJson('/testing/company-context/999999')
        ->assertBadRequest()
        ->assertJson([
            'success' => false,
            'message' => 'company.context.not_found',
        ]);
});
