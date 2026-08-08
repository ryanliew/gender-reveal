<?php

use App\Enums\Gender;
use App\Events\GenderRevealed;
use App\Events\PartyReset;
use App\Models\Guest;
use App\PartyState;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config(['party.host_secret' => 'sekret', 'party.host_pin' => '1234']);
});

test('host page 404s on wrong secret and renders on the right one', function () {
    $this->get('/host/nope')->assertNotFound();

    $this->get('/host/sekret')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Host')
            ->where('secret', 'sekret'));
});

test('reveal requires the correct pin', function () {
    Event::fake([GenderRevealed::class]);

    $this->post('/host/sekret/reveal', ['pin' => '9999', 'gender' => 'boy'])
        ->assertSessionHasErrors('pin');

    expect(PartyState::revealedGender())->toBeNull();
    Event::assertNotDispatched(GenderRevealed::class);

    $this->post('/host/sekret/reveal', ['pin' => '1234', 'gender' => 'boy'])
        ->assertRedirect();

    expect(PartyState::revealedGender())->toBe(Gender::Boy);
    Event::assertDispatched(GenderRevealed::class);
});

test('reveal 404s on wrong secret even with the right pin', function () {
    $this->post('/host/nope/reveal', ['pin' => '1234', 'gender' => 'boy'])
        ->assertNotFound();
});

test('reset clears the party and broadcasts', function () {
    Event::fake([PartyReset::class]);
    Guest::factory()->count(2)->create();
    PartyState::reveal(Gender::Girl);

    $this->post('/host/sekret/reset', ['pin' => '1234'])->assertRedirect();

    expect(Guest::count())->toBe(0)
        ->and(PartyState::revealedGender())->toBeNull();
    Event::assertDispatched(PartyReset::class);
});

test('reset requires the correct pin', function () {
    Event::fake([PartyReset::class]);
    Guest::factory()->create();

    $this->post('/host/sekret/reset', ['pin' => '0000'])
        ->assertSessionHasErrors('pin');

    expect(Guest::count())->toBe(1);
    Event::assertNotDispatched(PartyReset::class);
});
