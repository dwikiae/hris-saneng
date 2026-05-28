<?php

namespace Tests\Unit\Core\Company;

use App\Core\Company\Application\CompanyContext;
use App\Models\Company;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CompanyContextTest extends TestCase
{
    public function test_it_tracks_the_resolved_company(): void
    {
        $company = new Company(['name' => 'PT Context', 'legal_name' => 'PT Context']);
        $company->setAttribute('id', 42);

        $context = new CompanyContext;
        $context->set($company);

        $this->assertTrue($context->hasCompany());
        $this->assertSame($company, $context->company());
        $this->assertSame(42, $context->companyId());
    }

    public function test_it_requires_company_before_access(): void
    {
        $context = new CompanyContext;

        $this->assertFalse($context->hasCompany());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('company.context.required');

        $context->company();
    }

    public function test_it_clears_the_resolved_company(): void
    {
        $company = new Company(['name' => 'PT Context', 'legal_name' => 'PT Context']);
        $company->setAttribute('id', 42);

        $context = new CompanyContext;
        $context->set($company);
        $context->clear();

        $this->assertFalse($context->hasCompany());
    }
}
