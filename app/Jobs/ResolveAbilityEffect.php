<?php

namespace App\Jobs;

use App\Models\Game;
use App\Models\Move;
use App\Models\AbilityLog;
use App\Services\ChessBoardService;
use App\Events\MoveProcessed;
use App\Events\PieceCaptured;
use App\Events\GameEnded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResolveAbilityEffect implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $game;
    protected $moveData;
    protected $playerType;
    protected $chessService;

    public function __construct(Game $game, array $moveData, string $playerType)
    {
        $this->game = $game;
        $this->moveData = $moveData;
        $this->playerType = $playerType;
        $this->chessService = new ChessBoardService();
    }

    public function handle(): void
    {
        $barValue = ($this->playerType === 'player') ? $this->game->player_ability_bar : $this->game->ai_ability_bar;
        $abilityKey = ($this->playerType === 'player') ? $this->game->player_ability : $this->game->ai_ability;

        if ($barValue < 100 && !config('app.debug')) {
            Log::warning("Attempted to resolve ability without 100% bar for game {$this->game->id}");
            return;
        }

        $isSuperPawn = ($abilityKey === 'super_pawn');
        if (!$this->chessService->isValidMove($this->game->board_state, $this->moveData, $isSuperPawn)) {
            Log::error("Invalid Ability move attempted: {$this->moveData['from']} to {$this->moveData['to']}");
            return;
        }

        $oldFen = $this->game->board_state;
        $result = $this->chessService->applyMove($oldFen, $this->moveData);
        $notation = $this->chessService->generateAlgebraic($oldFen, $result, $this->moveData);

        Move::create([
            'game_id' => $this->game->id,
            'turn_number' => $this->game->moves()->count() + 1,
            'piece' => $result['piece'],
            'from_square' => $this->moveData['from'],
            'to_square' => $this->moveData['to'],
            'captured_piece' => $result['captured_piece'],
            'fen_after' => $result['fen'],
            'notation' => $notation,
        ]);

        AbilityLog::create([
            'game_id' => $this->game->id,
            'player_type' => $this->playerType,
            'ability_name' => $abilityKey,
            'turn_used' => $this->game->moves()->count(),
        ]);

        if ($this->playerType === 'player') {
            $this->game->player_ability_bar = 0;
            $this->game->recordMove('w');
            $this->game->current_turn = 'black';
        } else {
            $this->game->ai_ability_bar = 0;
            $this->game->recordMove('b');
            $this->game->current_turn = 'white';
        }

        if ($result['captured_piece']) {
            PieceCaptured::dispatch($this->game, $result['captured_piece'], $this->chessService->getPieceColor($result['captured_piece']));
        }

        $this->game->board_state = $result['fen'];
        
        if ($this->chessService->isCheckmate($result['fen'])) {
            $this->game->status = 'checkmate';
        } elseif ($this->chessService->isStalemate($result['fen'])) {
            $this->game->status = 'draw (stalemate)';
        } elseif ($this->chessService->isInsufficientMaterial($result['fen'])) {
            $this->game->status = 'draw (insufficient material)';
        }

        $this->game->save();

        MoveProcessed::dispatch($this->game, ($this->playerType === 'player' ? 'white' : 'black'), $this->moveData['from'], $this->moveData['to'], $notation);
        if ($this->game->status !== 'active') {
            GameEnded::dispatch($this->game);
        }
    }
}
