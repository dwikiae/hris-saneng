<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeePhoto;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * @var list<string>
     */
    private array $relations = [
        'user.roles',
        'department',
        'position',
    ];

    public function __construct(
        private readonly Employee $model,
        private readonly ArchiveService $archiveService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function index(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Employee::query()
            ->with($this->relations)
            ->orderBy('name');

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function show(int $id): Employee
    {
        /** @var Employee $employee */
        $employee = $this->model->newQuery()
            ->withoutGlobalScope('not_archived')
            ->with($this->relations)
            ->findOrFail($id);

        return $employee;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee
    {
        /** @var Employee $employee */
        $employee = $this->model->newQuery()->create($data);

        return $employee->load($this->relations);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee->refresh()->load($this->relations);
    }

    public function archive(Employee $employee): void
    {
        $this->archiveService->archive($employee);
    }

    /**
     * @return Collection<int, EmployeeDocument>
     */
    public function documents(Employee $employee): Collection
    {
        return $employee->documents()
            ->orderBy('document_type')
            ->orderByDesc('uploaded_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDocument(Employee $employee, array $data): EmployeeDocument
    {
        /** @var EmployeeDocument $document */
        $document = $employee->documents()->create(array_merge($data, [
            'company_id' => $employee->getAttribute('company_id'),
        ]));

        return $document;
    }

    public function documentForEmployee(Employee $employee, int $documentId): EmployeeDocument
    {
        /** @var EmployeeDocument $document */
        $document = $employee->documents()->findOrFail($documentId);

        return $document;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPhoto(Employee $employee, array $data): EmployeePhoto
    {
        /** @var EmployeePhoto $photo */
        $photo = $employee->photo()->create(array_merge($data, [
            'company_id' => $employee->getAttribute('company_id'),
        ]));

        return $photo;
    }

    public function latestPhoto(Employee $employee): ?EmployeePhoto
    {
        /** @var EmployeePhoto|null $photo */
        $photo = $employee->photo()->first();

        return $photo;
    }

    /**
     * @param  Builder<Employee>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder|QueryBuilder $query, array $filters): void
    {
        if (array_key_exists('status', $filters)) {
            $query->where('status', (string) $filters['status']);
        }

        if (array_key_exists('department_id', $filters)) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (array_key_exists('position_id', $filters)) {
            $query->where('position_id', (int) $filters['position_id']);
        }

        if (! array_key_exists('search', $filters) || $filters['search'] === null || $filters['search'] === '') {
            return;
        }

        $search = (string) $filters['search'];

        $query->where(function ($query) use ($search): void {
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('employee_number', 'like', '%'.$search.'%');
        });
    }
}
