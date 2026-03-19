<?php

namespace App\Listeners;

use App\Events\GameEnded;
use App\Jobs\AnalyseGameReplay;

class AnalyzeReplayOnEnd
{
    public function handle(GameEnded $event): void
    {
        AnalyseGameReplay::dispatch($event->game);
    }
}
