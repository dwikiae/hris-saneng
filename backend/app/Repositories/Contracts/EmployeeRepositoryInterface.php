<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeePhoto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function index(array $filters, int $perPage): LengthAwarePaginator;

    public function show(int $id): Employee;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee;

    public function archive(Employee $employee): void;

    /**
     * @return array<int, string>
     */
    public function employeeNumbersForCompanyIncludingArchived(int $companyId): array;

    public function employmentTypeIdForContractType(int $companyId, string $contractType): ?int;

    /**
     * @return Collection<int, EmployeeDocument>
     */
    public function documents(Employee $employee): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDocument(Employee $employee, array $data): EmployeeDocument;

    public function documentForEmployee(Employee $employee, int $documentId): EmployeeDocument;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPhoto(Employee $employee, array $data): EmployeePhoto;

    public function latestPhoto(Employee $employee): ?EmployeePhoto;
}
