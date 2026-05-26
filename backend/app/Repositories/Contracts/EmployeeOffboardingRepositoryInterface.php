<?php

namespace App\Repositories\Contracts;

use App\Models\EmployeeOffboarding;
use App\Models\EmployeeOffboardingItem;
use App\Models\OffboardingTemplate;

interface EmployeeOffboardingRepositoryInterface
{
    public function findTemplateByReason(int $companyId, int $terminationReasonId): ?OffboardingTemplate;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EmployeeOffboarding;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createItem(EmployeeOffboarding $offboarding, array $data): EmployeeOffboardingItem;

    public function findById(int $id): EmployeeOffboarding;

    public function findItem(int $offboardingId, int $itemId): EmployeeOffboardingItem;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(EmployeeOffboardingItem $item, array $data): EmployeeOffboardingItem;

    public function hasIncompleteMandatoryItems(EmployeeOffboarding $offboarding): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeOffboarding $offboarding, array $data): EmployeeOffboarding;
}
