<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeChatterMessage;
use App\Models\EmployeeDocument;
use App\Models\EmployeePhoto;
use App\Models\User;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * @var list<string>
     */
    private array $relations = [
        'user.roles',
        'department',
        'position',
        'employmentType',
        'division',
        'unit',
        'jobLevel',
        'workLocation',
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
        return $this->findAll($filters, $perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function findAll(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Employee::query()
            ->with($this->relations)
            ->orderBy($this->sortColumn($filters), $this->sortDirection($filters));

        $this->applyFilters($query, $filters);

        return $query->paginate(min(max($perPage, 1), 100));
    }

    public function show(int $id): Employee
    {
        return $this->findById($id);
    }

    public function findById(int $id): Employee
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

    public function generateEmployeeNumber(int $companyId): string
    {
        return DB::transaction(function () use ($companyId): string {
            $year = date('Y');
            $last = $this->model->newQuery()
                ->withoutGlobalScope('company')
                ->where('company_id', $companyId)
                ->where('employee_number', 'like', "EMP-{$year}-%")
                ->lockForUpdate()
                ->max('employee_number');

            $next = is_string($last) ? ((int) substr($last, -4)) + 1 : 1;

            return 'EMP-'.$year.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }

    public function createSystemLog(Employee $employee, string $message): EmployeeChatterMessage
    {
        /** @var EmployeeChatterMessage $chatterMessage */
        $chatterMessage = $employee->chatterMessages()->create([
            'company_id' => $employee->getAttribute('company_id'),
            'user_id' => null,
            'type' => EmployeeChatterMessage::SYSTEM_LOG,
            'message' => $message,
        ]);

        return $chatterMessage;
    }

    public function forceUpdate(Employee $employee, array $data): Employee
    {
        $employee->forceFill($data)->save();

        return $employee->refresh()->load($this->relations);
    }

    public function deactivateLinkedUser(Employee $employee): void
    {
        $userId = $employee->getAttribute('user_id');

        /** @var User|null $user */
        $user = $userId === null
            ? User::query()->where('employee_id', $employee->getKey())->first()
            : User::query()->find($userId);

        $user?->forceFill(['active' => false])->save();
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
        if (($filters['include_archived'] ?? false) === true || ($filters['include_archived'] ?? null) === 'true') {
            $query->withoutGlobalScope('not_archived');
        }

        if (array_key_exists('status', $filters)) {
            $query->where('status', (string) $filters['status']);
        }

        if (array_key_exists('department_id', $filters)) {
            $query->where('department_id', (int) $filters['department_id']);
        }

        if (array_key_exists('position_id', $filters)) {
            $query->where('position_id', (int) $filters['position_id']);
        }

        if (array_key_exists('employee_type_id', $filters)) {
            $query->where('employment_type_id', (int) $filters['employee_type_id']);
        }

        if (array_key_exists('employment_type_id', $filters)) {
            $query->where('employment_type_id', (int) $filters['employment_type_id']);
        }

        if (! array_key_exists('search', $filters) || $filters['search'] === null || $filters['search'] === '') {
            return;
        }

        $search = (string) $filters['search'];

        $query->where(function ($query) use ($search): void {
            $query->where('full_name', 'like', '%'.$search.'%')
                ->orWhere('name', 'like', '%'.$search.'%')
                ->orWhere('employee_number', 'like', '%'.$search.'%');
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function sortColumn(array $filters): string
    {
        return match ($filters['sort'] ?? 'name') {
            'join_date' => 'join_date',
            'employee_number' => 'employee_number',
            default => 'name',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function sortDirection(array $filters): string
    {
        return ($filters['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
    }
}
