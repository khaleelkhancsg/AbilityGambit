<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PieceCaptured implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $game;
    public $capturedPiece;
    public $capturedPieceColor;

    public function __construct(Game $game, string $capturedPiece, string $capturedPieceColor)
    {
        $this->game = $game;
        $this->capturedPiece = $capturedPiece;
        $this->capturedPieceColor = $capturedPieceColor;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('game.' . $this->game->id),
        ];
    }
}
