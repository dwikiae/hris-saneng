<?php

namespace App\Models\Recruitment;

use App\Contracts\Archivable;
use App\Enums\Recruitment\TestQuestionType;
use App\Models\Concerns\HasArchive;
use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class TestQuestion extends Model implements Archivable
{
    use HasArchive;
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'test_id',
        'type',
        'question',
        'pilihan',
        'answer_key',
        'bobot',
        'sort_order',
        'is_active',
        'archived_at',
        'archived_by',
        'created_by',
        'updated_by',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    protected function casts(): array
    {
        return [
            'type' => TestQuestionType::class,
            'pilihan' => 'array',
            'bobot' => 'decimal:2',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
