<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameEnded
{
    use Dispatchable, SerializesModels;

    public $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }
}
