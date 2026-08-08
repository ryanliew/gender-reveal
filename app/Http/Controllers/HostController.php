<?php

namespace App\Http\Controllers;

use App\Events\GenderRevealed;
use App\Events\PartyReset;
use App\Http\Requests\ResetRequest;
use App\Http\Requests\RevealRequest;
use App\PartyState;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HostController extends Controller
{
    public function show(string $secret): Response
    {
        $this->authorizeSecret($secret);

        return Inertia::render('Host', [
            'secret' => $secret,
        ]);
    }

    public function reveal(RevealRequest $request, string $secret): RedirectResponse
    {
        $this->authorizeSecret($secret);

        PartyState::reveal($request->gender());
        GenderRevealed::dispatch($request->gender());

        return back();
    }

    public function reset(ResetRequest $request, string $secret): RedirectResponse
    {
        $this->authorizeSecret($secret);

        PartyState::reset();
        PartyReset::dispatch();

        return back();
    }

    private function authorizeSecret(string $secret): void
    {
        abort_unless(hash_equals((string) config('party.host_secret'), $secret), 404);
    }
}
