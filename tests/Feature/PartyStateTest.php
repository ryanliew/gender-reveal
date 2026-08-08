<?php

use App\Enums\Gender;
use App\Models\Guest;
use App\PartyState;

test('reveal stores gender and reset clears everything', function () {
    Guest::factory()->count(3)->create();

    expect(PartyState::revealedGender())->toBeNull();

    PartyState::reveal(Gender::Girl);
    expect(PartyState::revealedGender())->toBe(Gender::Girl);

    PartyState::reset();
    expect(PartyState::revealedGender())->toBeNull()
        ->and(Guest::count())->toBe(0);
});
