<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasCompany
{
    protected static function bootHasCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            $user = Auth::user();

            if ($user instanceof User) {
                if ($user->isInstanceAdmin()) {
                    return;
                }

                $companyId = $user->company_id;
            } else {
                $companyId = (int) config('company.default_id', 1);
            }

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
