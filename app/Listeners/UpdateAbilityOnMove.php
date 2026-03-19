<?php

namespace App\Listeners;

use App\Events\MoveProcessed;
use App\Jobs\UpdateAbilityBar;

class UpdateAbilityOnMove
{
    public function handle(MoveProcessed $event): void
    {
        UpdateAbilityBar::dispatch($event->game, $event->side);
    }
}
