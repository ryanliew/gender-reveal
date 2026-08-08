<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class PartyReset implements ShouldBroadcastNow
{
    use Dispatchable;

    public function broadcastOn(): Channel
    {
        return new Channel('party');
    }
}
