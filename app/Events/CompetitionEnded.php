<?php

namespace App\Events;

use App\Models\Competition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompetitionEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Competition $competition,
        public readonly array $ranking,
    ) {}

    public function broadcastWith(): array
    {
        return [
            'ranking'     => $this->ranking,
            'results_url' => route('competition.results', $this->competition->room_code),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('competition.' . $this->competition->room_code),
        ];
    }
}
