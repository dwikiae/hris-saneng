<?php

namespace App\Enums\Recruitment;

enum ApplicantDocumentType: string
{
    case Cv = 'cv';
    case Portfolio = 'portfolio';
    case Certificate = 'certificate';
    case IdentityCard = 'identity_card';
    case Ktp = 'ktp';
    case Kk = 'kk';
    case Npwp = 'npwp';
    case Rekening = 'rekening';
    case BpjsKesehatan = 'bpjs_kesehatan';
    case BpjsKetenagakerjaan = 'bpjs_ketenagakerjaan';
    case Other = 'other';
}
