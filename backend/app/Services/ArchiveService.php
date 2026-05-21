<?php

namespace App\Services;

use App\Contracts\Archivable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ArchiveService
{
    public function archive(Model $model): void
    {
        $this->ensureArchivable($model);

        $model->forceFill([
            'archived_at' => now(),
            'archived_by' => Auth::id(),
        ])->save();
    }

    public function restore(Model $model): void
    {
        $this->ensureArchivable($model);

        $model->forceFill([
            'archived_at' => null,
            'archived_by' => null,
        ])->save();

        $activity = activity()
            ->useLog($model->getTable())
            ->performedOn($model)
            ->event('restored');

        $causer = Auth::user();

        if ($causer instanceof Model) {
            $activity->causedBy($causer);
        }

        $activity->log($model->getTable().'.restored');
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $filters
     */
    public function getArchivedRecords(string $modelClass, int $perPage, array $filters = []): LengthAwarePaginator
    {
        if (! is_subclass_of($modelClass, Model::class) || ! is_subclass_of($modelClass, Archivable::class)) {
            throw new InvalidArgumentException('archive.model_not_archivable');
        }

        $model = new $modelClass;

        $query = $model->newQuery()
            ->withoutGlobalScope('not_archived')
            ->whereNotNull($model->getTable().'.archived_at');

        if (array_key_exists('archived_by', $filters)) {
            $query->where($model->getTable().'.archived_by', $filters['archived_by']);
        }

        if (array_key_exists('date_from', $filters)) {
            $query->whereDate($model->getTable().'.archived_at', '>=', $filters['date_from']);
        }

        if (array_key_exists('date_to', $filters)) {
            $query->whereDate($model->getTable().'.archived_at', '<=', $filters['date_to']);
        }

        return $query
            ->orderByDesc($model->getTable().'.archived_at')
            ->paginate($perPage);
    }

    private function ensureArchivable(Model $model): void
    {
        if (! $model instanceof Archivable) {
            throw new InvalidArgumentException('archive.model_not_archivable');
        }
    }
}
