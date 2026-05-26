<?php

namespace App\Models;

use App\Models\Concerns\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeChatterMention extends Model
{
    use HasCompany;
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'chatter_message_id',
        'mentioned_user_id',
        'notified_at',
        'created_at',
    ];

    public function chatterMessage(): BelongsTo
    {
        return $this->belongsTo(EmployeeChatterMessage::class, 'chatter_message_id');
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
