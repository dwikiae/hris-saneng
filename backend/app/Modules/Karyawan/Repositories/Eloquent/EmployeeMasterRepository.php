<?php

namespace App\Modules\Karyawan\Repositories\Eloquent;

use App\Modules\Karyawan\Repositories\Contracts\EmployeeMasterRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EmployeeMasterRepository implements EmployeeMasterRepositoryInterface
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(private readonly string $modelClass) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Model>
     */
    public function all(int $companyId, array $filters = []): Collection
    {
        $query = $this->modelClass::query()
            ->forCompany($companyId)
            ->orderBy($this->orderColumn())
            ->orderBy('name');

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (($filters['search'] ?? '') !== '') {
            $search = (string) $filters['search'];
            $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        return $query->get();
    }

    public function find(int $companyId, int $id, bool $withArchived = false): ?Model
    {
        $query = $this->modelClass::query()->forCompany($companyId);

        if ($withArchived) {
            $query->withArchived();
        }

        return $query->whereKey($id)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, array $data): Model
    {
        return $this->modelClass::query()->create(array_merge($data, ['company_id' => $companyId]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $companyId, int $id, array $data): Model
    {
        $record = $this->find($companyId, $id);

        if (! $record instanceof Model) {
            throw (new ModelNotFoundException)->setModel($this->modelClass, $id);
        }

        $record->update($data);

        return $record->refresh();
    }

    public function archive(int $companyId, int $id, int $actorId): bool
    {
        $record = $this->find($companyId, $id);

        if (! $record instanceof Model || ! method_exists($record, 'archive')) {
            return false;
        }

        $record->archive($actorId);

        return true;
    }

    public function restore(int $companyId, int $id): bool
    {
        $record = $this->find($companyId, $id, true);

        if (! $record instanceof Model) {
            return false;
        }

        $record->update(['archived_at' => null, 'archived_by' => null]);

        return true;
    }

    private function orderColumn(): string
    {
        return in_array('order', (new $this->modelClass)->getFillable(), true) ? 'order' : 'code';
    }
}
