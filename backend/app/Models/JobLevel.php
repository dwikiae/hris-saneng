<?php

namespace App\Models;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLevel extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'sort_order',
        'is_active',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
