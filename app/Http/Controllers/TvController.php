<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\PartyState;
use Inertia\Inertia;
use Inertia\Response;

class TvController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Tv', [
            'guests' => Guest::query()->latest()->get()->map->publicPayload()->all(),
            'revealedGender' => PartyState::revealedGender()?->value,
            'joinUrl' => route('home'),
        ]);
    }
}
