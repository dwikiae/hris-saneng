<?php

namespace App\Enums\Recruitment;

enum ApplicantBlacklistStatus: string
{
    case Active = 'active';
    case Lifted = 'lifted';
}
