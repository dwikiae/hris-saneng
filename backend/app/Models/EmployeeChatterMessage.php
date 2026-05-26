<?php

namespace App\Models;

use App\Models\Concerns\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeChatterMessage extends Model
{
    use HasCompany;
    use HasFactory;

    public const SYSTEM_LOG = 'system_log';
    public const MANUAL_MESSAGE = 'manual_message';

    protected $fillable = [
        'company_id',
        'employee_id',
        'user_id',
        'type',
        'message',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(EmployeeChatterMention::class, 'chatter_message_id');
    }
}
