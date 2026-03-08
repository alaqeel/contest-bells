<?php

namespace App\Events;

use App\Models\Competition;
use App\Models\Contestant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContestantClaimed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Competition $competition,
        public readonly Contestant $contestant,
    ) {}

    public function broadcastWith(): array
    {
        return [
            'contestant_id'   => $this->contestant->id,
            'contestant_name' => $this->contestant->display_name,
            'claimed_at'      => $this->contestant->claimed_at?->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('competition.' . $this->competition->room_code),
        ];
    }
}
