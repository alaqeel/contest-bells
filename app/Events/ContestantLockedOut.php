<?php

namespace App\Events;

use App\Models\Competition;
use App\Models\Contestant;
use App\Models\Round;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContestantLockedOut implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Competition $competition,
        public readonly Round $round,
        public readonly Contestant $contestant,
        public readonly string $lockedUntil,
    ) {}

    public function broadcastWith(): array
    {
        return [
            'round_id'       => $this->round->id,
            'contestant_id'  => $this->contestant->id,
            'locked_until'   => $this->lockedUntil,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('competition.' . $this->competition->room_code),
        ];
    }
}
