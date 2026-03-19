<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MoveProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $game;
    public $side;
    public $from;
    public $to;
    public $notation;

    public function __construct(Game $game, string $side, ?string $from = null, ?string $to = null, ?string $notation = null)
    {
        $this->game = $game;
        $this->side = $side;
        $this->from = $from;
        $this->to = $to;
        $this->notation = $notation;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('game.' . $this->game->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'game' => $this->game->fresh(),
            'side' => $this->side,
            'last_move' => [
                'from' => $this->from,
                'to' => $this->to,
                'notation' => $this->notation,
            ],
        ];
    }
}
