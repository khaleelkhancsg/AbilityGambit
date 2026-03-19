<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbilityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_type',
        'ability_name',
        'turn_used',
    ];

    /**
     * Get the game that the ability log belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
