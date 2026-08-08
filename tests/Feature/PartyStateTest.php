<?php

use App\Enums\Gender;
use App\Models\Guest;
use App\PartyState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('reset deletes uploaded photos', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('me.jpg')->store('avatars', 'public');
    Guest::factory()->create(['avatar' => null, 'photo_path' => $path]);

    PartyState::reset();

    Storage::disk('public')->assertMissing($path);
});

test('reveal stores gender and reset clears everything', function () {
    Guest::factory()->count(3)->create();

    expect(PartyState::revealedGender())->toBeNull();

    PartyState::reveal(Gender::Girl);
    expect(PartyState::revealedGender())->toBe(Gender::Girl);

    PartyState::reset();
    expect(PartyState::revealedGender())->toBeNull()
        ->and(Guest::count())->toBe(0);
});
