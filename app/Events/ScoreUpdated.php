<?php

namespace App\Events;

use App\Models\Competition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Competition $competition,
        public readonly array $scoreboard, // [['id'=>1,'name'=>'Alice','score'=>2], ...]
    ) {}

    public function broadcastWith(): array
    {
        return ['scoreboard' => $this->scoreboard];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('competition.' . $this->competition->room_code),
        ];
    }
}
