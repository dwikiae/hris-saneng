<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasCompany
{
    protected static function bootHasCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            $companyId = (int) config('company.default_id', 1);

            if ($companyId > 0) {
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
