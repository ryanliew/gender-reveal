<?php

namespace App\Events;

use App\Models\Guest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class VoteUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    /** @var array{id: int, name: string, avatar: ?string, photoUrl: ?string, gender: string} */
    public array $guest;

    public function __construct(Guest $guest)
    {
        $this->guest = $guest->publicPayload();
    }

    public function broadcastOn(): Channel
    {
        return new Channel('party');
    }
}
