<?php

namespace App\Modules\Karyawan\Models;

use App\Models\Concerns\HasCompany;
use Illuminate\Database\Eloquent\Model;

class EmployeeModuleSetting extends Model
{
    use HasCompany;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];
}
