<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\InstanceAuditRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class InstanceAuditRepository implements InstanceAuditRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Activity>
     */
    public function allForExport(array $filters): Collection
    {
        /** @var Collection<int, Activity> $records */
        $records = $this->query($filters)->limit(5000)->get();

        return $records;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Activity>
     */
    private function query(array $filters): Builder
    {
        /** @var Builder<Activity> $query */
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest('created_at')
            ->latest('id');

        $logName = $this->stringFilter($filters, 'log_name') ?? $this->stringFilter($filters, 'module');
        $event = $this->stringFilter($filters, 'event') ?? $this->stringFilter($filters, 'action');

        if ($logName !== null) {
            $query->where('log_name', $logName);
        }

        if ($event !== null) {
            $query->where('event', $event);
        }

        if (($filters['causer_id'] ?? '') !== '') {
            $query->where('causer_id', (int) $filters['causer_id']);
        }

        if (($filters['date_from'] ?? '') !== '') {
            $query->whereDate('created_at', '>=', (string) $filters['date_from']);
        }

        if (($filters['date_to'] ?? '') !== '') {
            $query->whereDate('created_at', '<=', (string) $filters['date_to']);
        }

        if (($filters['actor'] ?? '') !== '') {
            $actor = (string) $filters['actor'];
            $query->where(function (Builder $query) use ($actor): void {
                if (ctype_digit($actor)) {
                    $query->orWhere('causer_id', (int) $actor);
                }

                $query->orWhereHasMorph(
                    'causer',
                    [User::class],
                    fn (Builder $query): Builder => $query
                        ->where('name', 'like', '%'.$actor.'%')
                        ->orWhere('email', 'like', '%'.$actor.'%')
                );
            });
        }

        if (($filters['search'] ?? '') !== '') {
            $search = (string) $filters['search'];
            $query->where(function (Builder $query) use ($search): void {
                $query->where('description', 'like', '%'.$search.'%')
                    ->orWhere('log_name', 'like', '%'.$search.'%')
                    ->orWhere('event', 'like', '%'.$search.'%')
                    ->orWhereHasMorph(
                        'causer',
                        [User::class],
                        fn (Builder $query): Builder => $query
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                    );
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function stringFilter(array $filters, string $key): ?string
    {
        $value = trim((string) ($filters[$key] ?? ''));

        return $value === '' ? null : $value;
    }
}
