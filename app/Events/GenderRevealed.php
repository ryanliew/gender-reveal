<?php

namespace App\Events;

use App\Enums\Gender;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class GenderRevealed implements ShouldBroadcastNow
{
    use Dispatchable;

    public string $gender;

    public function __construct(Gender $gender)
    {
        $this->gender = $gender->value;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('party');
    }
}
