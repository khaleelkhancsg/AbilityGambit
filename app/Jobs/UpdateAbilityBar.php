<?php

namespace App\Jobs;

use App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateAbilityBar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $game;
    protected $side;

    /**
     * Create a new job instance.
     * side: 'white' (player) or 'black' (ai)
     */
    public function __construct(Game $game, string $side)
    {
        $this->game = $game;
        $this->side = $side;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh game state to get the latest move counts
        $this->game->refresh();

        if ($this->side === 'white') {
            // Player just moved
            if ($this->game->player_move_count > 0 && $this->game->player_move_count % 5 === 0) {
                $this->game->player_ability_bar = min(100, $this->game->player_ability_bar + 5);
            }
        } else {
            // AI just moved
            if ($this->game->ai_move_count > 0 && $this->game->ai_move_count % 5 === 0) {
                $this->game->ai_ability_bar = min(100, $this->game->ai_ability_bar + 5);
            }
        }

        $this->game->save();
    }
}
