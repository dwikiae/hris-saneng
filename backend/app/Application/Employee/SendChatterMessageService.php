<?php

namespace App\Application\Employee;

use App\Jobs\Employee\SendChatterMentionNotificationJob;
use App\Models\Employee;
use App\Models\EmployeeChatterMessage;
use App\Repositories\Contracts\EmployeeChatterRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SendChatterMessageService
{
    public function __construct(private readonly EmployeeChatterRepositoryInterface $chatter) {}

    public function execute(Employee $employee, string $message): EmployeeChatterMessage
    {
        Gate::authorize('chatter.create');

        $actorId = (int) Auth::id();
        $record = $this->chatter->createManualMessage($employee, $actorId, $message);

        foreach ($this->mentionedUserIds($employee, $message) as $mentionedUserId) {
            $this->chatter->createMention($record, $mentionedUserId);
            SendChatterMentionNotificationJob::dispatch((int) $record->getKey(), $mentionedUserId);
        }

        return $record;
    }

    /**
     * @return list<int>
     */
    private function mentionedUserIds(Employee $employee, string $message): array
    {
        preg_match_all('/@([A-Za-z0-9_. -]+)/', $message, $matches);
        $names = array_values(array_unique(array_map('trim', $matches[1])));

        return $this->chatter->mentionedUserIdsByNames((int) $employee->getAttribute('company_id'), $names);
    }
}
