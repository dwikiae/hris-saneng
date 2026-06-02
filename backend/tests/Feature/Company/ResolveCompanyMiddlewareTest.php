<?php

use App\Core\Company\Application\CompanyContext;
use App\Http\Middleware\ResolveCompany;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
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

it('injects company context from route slug', function () {
    $company = Company::create(['name' => 'PT Slug Route', 'legal_name' => 'PT Slug Route', 'slug' => 'pt-slug-route']);

    $response = $this->getJson('/testing/company-context/'.$company->slug);

    $response
        ->assertOk()
        ->assertJson([
            'company_id' => $company->id,
            'request_company_id' => $company->id,
        ]);
});

it('injects company context from header slug', function () {
    $company = Company::create(['name' => 'PT Header Slug', 'legal_name' => 'PT Header Slug', 'slug' => 'pt-header-slug']);

    $response = $this->withHeader('X-Company-Id', $company->slug)
        ->getJson('/testing/company-context');

    $response
        ->assertOk()
        ->assertJson([
            'company_id' => $company->id,
            'request_company_id' => $company->id,
        ]);
});

it('injects company context from the authenticated company user', function () {
    $company = Company::create(['name' => 'PT User Context', 'legal_name' => 'PT User Context']);
    $user = User::create([
        'company_id' => $company->id,
        'name' => 'Company User',
        'email' => 'company.user.context@example.test',
        'password' => 'password',
    ]);

    $response = $this->actingAs($user)
        ->getJson('/testing/company-context');

    $response
        ->assertOk()
        ->assertJson([
            'company_id' => $company->id,
            'request_company_id' => $company->id,
        ]);
});

it('rejects company users that request another company context', function () {
    $ownCompany = Company::create(['name' => 'PT Own Context', 'legal_name' => 'PT Own Context']);
    $otherCompany = Company::create(['name' => 'PT Other Context', 'legal_name' => 'PT Other Context']);
    $user = User::create([
        'company_id' => $ownCompany->id,
        'name' => 'Company User',
        'email' => 'company.user.mismatch@example.test',
        'password' => 'password',
    ]);

    $this->actingAs($user)
        ->withHeader('X-Company-Id', (string) $otherCompany->id)
        ->getJson('/testing/company-context')
        ->assertBadRequest()
        ->assertJson([
            'success' => false,
            'message' => 'company.context.not_found',
        ]);
});

it('rejects company users that request another company context by slug', function () {
    $ownCompany = Company::create(['name' => 'PT Own Slug', 'legal_name' => 'PT Own Slug', 'slug' => 'pt-own-slug']);
    $otherCompany = Company::create(['name' => 'PT Other Slug', 'legal_name' => 'PT Other Slug', 'slug' => 'pt-other-slug']);
    $user = User::create([
        'company_id' => $ownCompany->id,
        'name' => 'Company User',
        'email' => 'company.user.slug.mismatch@example.test',
        'password' => 'password',
    ]);

    $this->actingAs($user)
        ->withHeader('X-Company-Id', $otherCompany->slug)
        ->getJson('/testing/company-context')
        ->assertBadRequest()
        ->assertJson([
            'success' => false,
            'message' => 'company.context.not_found',
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
