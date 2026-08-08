<?php

namespace App\Http\Controllers;

use App\Events\VoteUpdated;
use App\Http\Requests\StoreVoteRequest;
use App\Models\Guest;
use App\PartyState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        $existing = Guest::query()->where('device_uuid', $deviceUuid)->first();

        $data = [
            'name' => $request->validated('name'),
            'gender' => $request->validated('gender'),
        ];

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('avatars', 'public');
            $data['avatar'] = null;
        } elseif ($request->filled('avatar')) {
            $data['avatar'] = $request->validated('avatar');
            $data['photo_path'] = null;
        }
        // Otherwise the guest keeps their previously uploaded photo.

        if ($existing?->photo_path !== null
            && array_key_exists('photo_path', $data)
            && $data['photo_path'] !== $existing->photo_path) {
            Storage::disk('public')->delete($existing->photo_path);
        }

        $guest = Guest::query()->updateOrCreate(
            ['device_uuid' => $deviceUuid],
            $data,
        );

        VoteUpdated::dispatch($guest);

        return back()->withCookie(cookie('device_uuid', $deviceUuid, 60 * 24 * 365));
    }
}
