<?php

namespace App\Events;

use App\Models\Competition;
use App\Models\Round;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundReset implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Competition $competition,
        public readonly Round $round,
    ) {}

    public function broadcastWith(): array
    {
        return [
            'round_id' => $this->round->id,
            'status'   => $this->round->status->value,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('competition.' . $this->competition->room_code),
        ];
    }
}
