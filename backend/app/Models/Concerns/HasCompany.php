<?php

namespace App\Models\Concerns;

use App\Core\Company\Application\CompanyContext;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasCompany
{
    protected static function bootHasCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            $companyContext = app(CompanyContext::class);

            if ($companyContext->hasCompany()) {
                $query->where($query->getModel()->getTable().'.company_id', $companyContext->companyId());

                return;
            }

            $user = Auth::user();

            if (! $user instanceof User) {
                return;
            }

            if ($user->isInstanceAdmin()) {
                return;
            }

            $companyId = $user->company_id;

            if ($companyId !== null && $companyId > 0) {
                $query->where($query->getModel()->getTable().'.company_id', $companyId);
            }
        });
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->withoutGlobalScope('company')
            ->where($query->getModel()->getTable().'.company_id', $companyId);
    }

    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}
