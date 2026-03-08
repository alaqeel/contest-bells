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

class BuzzAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Competition $competition,
        public readonly Round $round,
        public readonly Contestant $contestant,
    ) {}

    public function broadcastWith(): array
    {
        return [
            'round_id'          => $this->round->id,
            'contestant_id'     => $this->contestant->id,
            'contestant_name'   => $this->contestant->display_name,
            'first_buzzed_at'   => $this->round->first_buzzed_at?->toIso8601String(),
            'answer_deadline_at'=> $this->round->answer_deadline_at?->toIso8601String(),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('competition.' . $this->competition->room_code),
        ];
    }
}
