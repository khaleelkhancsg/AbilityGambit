<?php

namespace App\Listeners;

use App\Events\PieceCaptured;

class UpdateAbilityOnCapture
{
    public function handle(PieceCaptured $event): void
    {
        $event->game->recordCapture($event->capturedPieceColor);
    }
}
