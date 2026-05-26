<?php

namespace App\Models;

use App\Contracts\Archivable;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffboardingTemplateItem extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'template_id',
        'item_name',
        'department_responsible',
        'is_mandatory',
        'sort_order',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(OffboardingTemplate::class, 'template_id');
    }

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'sort_order' => 'integer',
            'archived_at' => 'datetime',
        ];
    }
}
