<?php

use App\Models\Guest;

test('tv page lists guests and join url', function () {
    Guest::factory()->count(2)->create();

    $this->get('/tv')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Tv')
            ->has('guests', 2)
            ->where('revealedGender', null)
            ->where('joinUrl', route('home')));
});

test('tv guest payload does not leak device uuid', function () {
    Guest::factory()->create();

    $this->get('/tv')
        ->assertInertia(fn ($page) => $page->missing('guests.0.device_uuid'));
});
