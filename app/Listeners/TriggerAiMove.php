<?php

namespace App\Listeners;

use App\Events\MoveProcessed;
use App\Jobs\CalculateAiMove;

class TriggerAiMove
{
    public function handle(MoveProcessed $event): void
    {
        if ($event->side === 'white' && $event->game->status === 'active') {
            CalculateAiMove::dispatch($event->game);
        }
    }
}
