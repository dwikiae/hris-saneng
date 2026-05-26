<?php

use App\Domain\Employee\Rules\ConsentRequiredRule;

it('rejects missing consent timestamp', function () {
    $rule = new ConsentRequiredRule();

    expect($rule->passes(null, 'Saya menyetujui pemrosesan data pribadi.'))->toBeFalse();

    $rule->assert(null, 'Saya menyetujui pemrosesan data pribadi.');
})->throws(InvalidArgumentException::class)->group('employee-domain');

it('rejects missing consent text', function () {
    $rule = new ConsentRequiredRule();

    expect($rule->passes('2026-05-25 09:00:00', null))->toBeFalse()
        ->and($rule->passes('2026-05-25 09:00:00', ''))->toBeFalse()
        ->and($rule->passes('2026-05-25 09:00:00', '   '))->toBeFalse();

    $rule->assert('2026-05-25 09:00:00', '');
})->throws(InvalidArgumentException::class)->group('employee-domain');

it('accepts consent timestamp and consent text', function () {
    $rule = new ConsentRequiredRule();

    expect($rule->passes('2026-05-25 09:00:00', 'Saya menyetujui pemrosesan data pribadi.'))->toBeTrue();

    $rule->assert('2026-05-25 09:00:00', 'Saya menyetujui pemrosesan data pribadi.');
})->group('employee-domain');
