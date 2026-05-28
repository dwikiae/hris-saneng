<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;

trait InteractsWithLog
{
    /**
     * @return array<int, string>
     */
    public static function sensitiveAuditFields(): array
    {
        return [
            'password',
            'password_confirmation',
            'remember_token',
            'token',
            'api_token',
            'access_token',
            'refresh_token',
            'smtp_password',
            'secret',
            'nik',
            'npwp',
            'bank_account_number',
            'account_number',
            'salary',
            'allowances',
            'deductions',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        $table = $this->getTable();

        return LogOptions::defaults()
            ->useLogName($table)
            ->logOnly($this->activityLogAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(fn (string $eventName): string => $table.'.'.$eventName);
    }

    /**
     * @return array<int, string>
     */
    protected function activityLogAttributes(): array
    {
        return array_values(array_diff($this->getFillable(), $this->sensitiveActivityLogAttributes()));
    }

    /**
     * @return array<int, string>
     */
    protected function sensitiveActivityLogAttributes(): array
    {
        return self::sensitiveAuditFields();
    }
}
