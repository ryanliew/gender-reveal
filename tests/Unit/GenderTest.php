<?php

use App\Enums\Gender;

test('gender enum exposes boy and girl values', function () {
    expect(Gender::Boy->value)->toBe('boy')
        ->and(Gender::Girl->value)->toBe('girl')
        ->and(Gender::values())->toBe(['boy', 'girl']);
});
