<?php

namespace App\Core\FileStorage\Domain;

enum UploadProfile: string
{
    case PrivateDocument = 'private_document';
    case EmployeePhoto = 'employee_photo';
    case PublicAsset = 'public_asset';
    case ExportFile = 'export_file';
}
