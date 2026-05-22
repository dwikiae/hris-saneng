<?php

namespace App\Enums\Recruitment;

enum QuizSessionStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Completed = 'completed';
    case Scored = 'scored';
    case Expired = 'expired';
}
