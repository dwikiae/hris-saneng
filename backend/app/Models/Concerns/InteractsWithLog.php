<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;

trait InteractsWithLog
{
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
        return [
            'password',
            'remember_token',
            'nik',
            'npwp',
            'bank_account_number',
            'salary',
        ];
    }
}
