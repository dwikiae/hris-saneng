<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasArchive
{
    protected static function bootHasArchive(): void
    {
        static::addGlobalScope('not_archived', function (Builder $query) {
            $query->whereNull($query->getModel()->getTable().'.archived_at');
        });
    }

    public function archive(int $userId): void
    {
        $this->update([
            'archived_at' => now(),
            'archived_by' => $userId,
        ]);
    }

    public function scopeWithArchived(Builder $query): Builder
    {
        return $query->withoutGlobalScope('not_archived');
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        $query->whereNull($query->getModel()->getTable().'.archived_at');

        return $query;
    }

    public function scopeArchived(Builder $query): Builder
    {
        $query->whereNotNull($query->getModel()->getTable().'.archived_at');

        return $query;
    }
}
