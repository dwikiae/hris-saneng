<?php

use App\Domain\Employee\Rules\NikFormatRule;

it('rejects non numeric nik', function () {
    $rule = new NikFormatRule();

    expect($rule->passes('337401010101ABCD'))->toBeFalse();

    $rule->assert('337401010101ABCD');
})->throws(InvalidArgumentException::class)->group('employee-domain');

it('rejects nik shorter than sixteen digits', function () {
    $rule = new NikFormatRule();

    expect($rule->passes('337401010101001'))->toBeFalse();

    $rule->assert('337401010101001');
})->throws(InvalidArgumentException::class)->group('employee-domain');

it('rejects nik longer than sixteen digits', function () {
    $rule = new NikFormatRule();

    expect($rule->passes('33740101010100012'))->toBeFalse();

    $rule->assert('33740101010100012');
})->throws(InvalidArgumentException::class)->group('employee-domain');

it('accepts sixteen digit numeric nik', function () {
    $rule = new NikFormatRule();

    expect($rule->passes('3374010101010001'))->toBeTrue();

    $rule->assert('3374010101010001');
})->group('employee-domain');
