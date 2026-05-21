<?php

namespace App\Models;

use App\Models\Concerns\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasCompany;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
