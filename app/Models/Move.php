<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Move extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'turn_number',
        'piece',
        'from_square',
        'to_square',
        'captured_piece',
        'fen_after',
        'analysis',
        'notation',
    ];

    /**
     * Get the game that the move belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
