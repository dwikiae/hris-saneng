<?php

namespace App\Enums\Recruitment;

enum ApplicantSource: string
{
    case Website = 'website';
    case Referral = 'referral';
    case WalkIn = 'walk_in';
    case JobPortal = 'job_portal';
    case SocialMedia = 'social_media';
    case Internal = 'internal';
}
