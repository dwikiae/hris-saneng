<?php

namespace App\Enums\Recruitment;

enum TestQuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case Essay = 'essay';
    case TrueFalse = 'true_false';
}
