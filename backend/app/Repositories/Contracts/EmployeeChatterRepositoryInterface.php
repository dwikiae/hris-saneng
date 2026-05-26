<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use App\Models\EmployeeChatterMention;
use App\Models\EmployeeChatterMessage;

interface EmployeeChatterRepositoryInterface
{
    /**
     * @param  list<string>  $names
     * @return list<int>
     */
    public function mentionedUserIdsByNames(int $companyId, array $names): array;

    public function createManualMessage(Employee $employee, int $userId, string $message): EmployeeChatterMessage;

    public function createMention(EmployeeChatterMessage $message, int $mentionedUserId): EmployeeChatterMention;
}
