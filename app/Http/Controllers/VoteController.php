<?php

namespace App\Http\Controllers;

use App\Events\VoteUpdated;
use App\Http\Requests\StoreVoteRequest;
use App\Models\Guest;
use App\PartyState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VoteController extends Controller
{
    public function create(Request $request): Response
    {
        $guest = Guest::query()
            ->where('device_uuid', $request->cookie('device_uuid'))
            ->first();

        return Inertia::render('Join', [
            'guest' => $guest?->publicPayload(),
            'avatars' => Guest::AVATARS,
            'revealedGender' => PartyState::revealedGender()?->value,
        ]);
    }

    public function store(StoreVoteRequest $request): RedirectResponse
    {
        abort_if(PartyState::revealedGender() !== null, 403, 'The gender has already been revealed!');

        $cookie = $request->cookie('device_uuid');
        $deviceUuid = is_string($cookie) && $cookie !== '' ? $cookie : (string) Str::uuid();

        $guest = Guest::query()->updateOrCreate(
            ['device_uuid' => $deviceUuid],
            $request->validated(),
        );

        VoteUpdated::dispatch($guest);

        return back()->withCookie(cookie('device_uuid', $deviceUuid, 60 * 24 * 365));
    }
}
