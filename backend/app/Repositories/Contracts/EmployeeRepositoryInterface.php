<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeChatterMessage;
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

    /**
     * @param  array<string, mixed>  $filters
     */
    public function findAll(array $filters, int $perPage): LengthAwarePaginator;

    public function show(int $id): Employee;

    public function findById(int $id): Employee;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Employee;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data): Employee;

    public function archive(Employee $employee): void;

    public function generateEmployeeNumber(int $companyId): string;

    public function createSystemLog(Employee $employee, string $message): EmployeeChatterMessage;

    /**
     * @param  array<string, mixed>  $data
     */
    public function forceUpdate(Employee $employee, array $data): Employee;

    public function deactivateLinkedUser(Employee $employee): void;

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
