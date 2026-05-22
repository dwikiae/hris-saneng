<?php

namespace App\Enums\Recruitment;

enum ApplicantStatus: string
{
    case Active = 'active';
    case Passed = 'passed';
    case Failed = 'failed';
    case Withdrawn = 'withdrawn';
    case Blacklisted = 'blacklisted';
}
