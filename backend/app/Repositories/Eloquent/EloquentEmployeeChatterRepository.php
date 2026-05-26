<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Models\EmployeeChatterMention;
use App\Models\EmployeeChatterMessage;
use App\Models\User;
use App\Repositories\Contracts\EmployeeChatterRepositoryInterface;

class EloquentEmployeeChatterRepository implements EmployeeChatterRepositoryInterface
{
    public function mentionedUserIdsByNames(int $companyId, array $names): array
    {
        if ($names === []) {
            return [];
        }

        return User::query()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('name', $names)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function createManualMessage(Employee $employee, int $userId, string $message): EmployeeChatterMessage
    {
        /** @var EmployeeChatterMessage $chatterMessage */
        $chatterMessage = $employee->chatterMessages()->create([
            'company_id' => $employee->getAttribute('company_id'),
            'user_id' => $userId,
            'type' => EmployeeChatterMessage::MANUAL_MESSAGE,
            'message' => $message,
        ]);

        return $chatterMessage;
    }

    public function createMention(EmployeeChatterMessage $message, int $mentionedUserId): EmployeeChatterMention
    {
        /** @var EmployeeChatterMention $mention */
        $mention = $message->mentions()->create([
            'company_id' => $message->getAttribute('company_id'),
            'mentioned_user_id' => $mentionedUserId,
            'created_at' => now(),
        ]);

        return $mention;
    }
}
