<?php

namespace App\Enums\Recruitment;

enum ApplicantStage: string
{
    case Applied = 'applied';
    case Screening = 'screening';
    case Test = 'test';
    case TesTulis = 'tes_tulis';
    case TesKemampuan = 'tes_kemampuan';
    case Interview = 'interview';
    case Mcu = 'mcu';
    case Offering = 'offering';
    case Pemberkasan = 'pemberkasan';
    case Hired = 'hired';
    case Rejected = 'rejected';
}
