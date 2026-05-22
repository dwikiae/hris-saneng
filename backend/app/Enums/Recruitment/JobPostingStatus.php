<?php

namespace App\Enums\Recruitment;

enum JobPostingStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Archived = 'archived';
}
