<?php

use App\Enums\Gender;
use App\Events\VoteUpdated;
use App\Models\Guest;
use App\PartyState;
use Illuminate\Support\Facades\Event;
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

    $this->post('/vote', ['name' => 'Aunty May', 'avatar' => 'fox', 'gender' => 'girl'])
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
        ->post('/vote', ['name' => 'Uncle Tan', 'avatar' => 'bear', 'gender' => 'girl'])
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

test('invalid payloads are rejected', function (array $payload) {
    $this->post('/vote', $payload)->assertSessionHasErrors();

    expect(Guest::count())->toBe(0);
})->with([
    'missing name' => [['avatar' => 'fox', 'gender' => 'boy']],
    'unknown avatar' => [['name' => 'A', 'avatar' => 'dragon', 'gender' => 'boy']],
    'unknown gender' => [['name' => 'A', 'avatar' => 'fox', 'gender' => 'unsure']],
]);

test('voting after reveal is forbidden', function () {
    PartyState::reveal(Gender::Boy);

    $this->post('/vote', ['name' => 'Late Guy', 'avatar' => 'owl', 'gender' => 'boy'])
        ->assertForbidden();

    expect(Guest::count())->toBe(0);
});
