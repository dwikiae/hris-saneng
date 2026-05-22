<?php

namespace App\Models\Recruitment;

use App\Models\Concerns\HasCompany;
use App\Models\Concerns\InteractsWithLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class QuizAnswer extends Model
{
    use HasCompany;
    use HasFactory;
    use InteractsWithLog;
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'quiz_session_id',
        'test_question_id',
        'answer',
        'is_correct',
        'bobot_snapshot',
        'score',
        'created_by',
        'updated_by',
    ];

    public function quizSession(): BelongsTo
    {
        return $this->belongsTo(QuizSession::class);
    }

    public function testQuestion(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class);
    }

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'bobot_snapshot' => 'decimal:2',
            'score' => 'decimal:2',
        ];
    }
}
