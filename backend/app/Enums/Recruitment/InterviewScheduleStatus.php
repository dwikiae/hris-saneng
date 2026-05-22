<?php

namespace App\Enums\Recruitment;

enum InterviewScheduleStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}
