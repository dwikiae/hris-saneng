<?php

namespace App\Models;

use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;
    use HasArchive;
    use HasCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'is_active',
        'created_by',
        'updated_by',
        'archived_at',
        'archived_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
