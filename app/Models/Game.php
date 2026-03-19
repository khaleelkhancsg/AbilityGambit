<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_state',
        'current_turn',
        'player_ability_bar',
        'ai_ability_bar',
        'player_move_count',
        'ai_move_count',
        'status',
        'player_ability',
        'ai_ability',
        'difficulty',
    ];

    /**
     * Get the moves for the game.
     */
    public function moves(): HasMany
    {
        return $this->hasMany(Move::class);
    }

    public function moveHistory(): HasMany
    {
        return $this->hasMany(Move::class)->orderBy('turn_number', 'asc');
    }

    /**
     * Get the ability logs for the game.
     */
    /**
     * Update ability bars based on a capture.
     */
    public function recordCapture(string $capturedPieceColor): void
    {
        // Special case: Passive 'Reinforced Walls' logic is handled in ChessBoardService during validation.
        // This method just updates the bar.
        
        if ($capturedPieceColor === 'w') {
            // Player (White) piece taken
            $this->player_ability_bar = min(100, $this->player_ability_bar + 5);
            $this->ai_ability_bar = max(0, $this->ai_ability_bar - 5);
        } else {
            // AI (Black) piece taken
            $this->ai_ability_bar = min(100, $this->ai_ability_bar + 5);
            $this->player_ability_bar = max(0, $this->player_ability_bar - 5);
        }
    }

    /**
     * Increment move count and update ability bar if needed.
     */
    public function recordMove(string $side): void
    {
        if ($side === 'w') {
            $this->player_move_count++;
            if ($this->player_move_count % 5 === 0) {
                $this->player_ability_bar = min(100, $this->player_ability_bar + 5);
            }
        } else {
            $this->ai_move_count++;
            if ($this->ai_move_count % 5 === 0) {
                $this->ai_ability_bar = min(100, $this->ai_ability_bar + 5);
            }
        }
    }
}
