<?php

use App\Enums\Gender;
use App\Events\VoteUpdated;
use App\Models\Guest;
use App\PartyState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('join page renders with no vote', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Join')
            ->where('guest', null)
            ->where('avatars', Guest::AVATARS)
            ->where('revealedGender', null));
});

test('posting a vote creates a guest and broadcasts', function () {
    Event::fake([VoteUpdated::class]);

    $this->post('/vote', ['name' => 'Aunty May', 'avatar' => 'fox-front', 'gender' => 'girl'])
        ->assertRedirect()
        ->assertCookie('device_uuid');

    expect(Guest::count())->toBe(1)
        ->and(Guest::first()->gender)->toBe(Gender::Girl);

    Event::assertDispatched(VoteUpdated::class);
});

test('same device updates its vote instead of duplicating', function () {
    Event::fake([VoteUpdated::class]);
    $uuid = (string) Str::uuid();
    Guest::factory()->create(['device_uuid' => $uuid, 'gender' => 'boy']);

    $this->withUnencryptedCookie('device_uuid', $uuid)
        ->post('/vote', ['name' => 'Uncle Tan', 'avatar' => 'light-bear', 'gender' => 'girl'])
        ->assertRedirect();

    expect(Guest::count())->toBe(1)
        ->and(Guest::first()->name)->toBe('Uncle Tan')
        ->and(Guest::first()->gender)->toBe(Gender::Girl);
});

test('join page shows existing vote for known device', function () {
    $guest = Guest::factory()->create();

    $this->withUnencryptedCookie('device_uuid', $guest->device_uuid)
        ->get('/')
        ->assertInertia(fn ($page) => $page->where('guest.id', $guest->id));
});

test('a photo can be uploaded as the avatar', function () {
    Storage::fake('public');
    Event::fake([VoteUpdated::class]);

    $this->post('/vote', [
        'name' => 'Selfie Sam',
        'photo' => UploadedFile::fake()->image('me.jpg', 600, 800),
        'gender' => 'boy',
    ])->assertRedirect();

    $guest = Guest::first();

    expect($guest->avatar)->toBeNull()
        ->and($guest->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($guest->photo_path);
    Event::assertDispatched(VoteUpdated::class);
});

test('replacing a photo with a preset deletes the old file', function () {
    Storage::fake('public');
    Event::fake([VoteUpdated::class]);
    $path = UploadedFile::fake()->image('old.jpg')->store('avatars', 'public');
    $guest = Guest::factory()->create(['avatar' => null, 'photo_path' => $path]);

    $this->withUnencryptedCookie('device_uuid', $guest->device_uuid)
        ->post('/vote', ['name' => 'Back To Fox', 'avatar' => 'fox-front', 'gender' => 'girl'])
        ->assertRedirect();

    Storage::disk('public')->assertMissing($path);
    expect($guest->refresh()->avatar)->toBe('fox-front')
        ->and($guest->photo_path)->toBeNull();
});

test('updating a vote without touching the avatar keeps the photo', function () {
    Storage::fake('public');
    Event::fake([VoteUpdated::class]);
    $path = UploadedFile::fake()->image('keep.jpg')->store('avatars', 'public');
    $guest = Guest::factory()->create(['avatar' => null, 'photo_path' => $path, 'gender' => 'boy']);

    $this->withUnencryptedCookie('device_uuid', $guest->device_uuid)
        ->post('/vote', ['name' => 'Changed Mind', 'gender' => 'girl'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertExists($path);
    expect($guest->refresh()->photo_path)->toBe($path)
        ->and($guest->gender)->toBe(Gender::Girl);
});

test('a vote with neither preset nor photo is rejected', function () {
    $this->post('/vote', ['name' => 'No Face', 'gender' => 'boy'])
        ->assertSessionHasErrors('avatar');

    expect(Guest::count())->toBe(0);
});

test('invalid payloads are rejected', function (array $payload) {
    $this->post('/vote', $payload)->assertSessionHasErrors();

    expect(Guest::count())->toBe(0);
})->with([
    'missing name' => [['avatar' => 'fox-front', 'gender' => 'boy']],
    'unknown avatar' => [['name' => 'A', 'avatar' => 'dragon', 'gender' => 'boy']],
    'unknown gender' => [['name' => 'A', 'avatar' => 'fox-front', 'gender' => 'unsure']],
]);

test('voting after reveal is forbidden', function () {
    PartyState::reveal(Gender::Boy);

    $this->post('/vote', ['name' => 'Late Guy', 'avatar' => 'owl', 'gender' => 'boy'])
        ->assertForbidden();

    expect(Guest::count())->toBe(0);
});
