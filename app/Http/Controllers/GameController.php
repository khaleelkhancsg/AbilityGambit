<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Move;
use App\Services\ChessBoardService;
use App\Services\AbilityService;
use App\Jobs\ResolveAbilityEffect;
use App\Events\MoveProcessed;
use App\Events\PieceCaptured;
use App\Events\GameEnded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    protected $chessService;
    protected $abilityService;

    public function __construct()
    {
        $this->chessService = new ChessBoardService();
        $this->abilityService = new AbilityService();
    }

    /**
     * Start a new game.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'player_ability' => 'required|string|in:' . implode(',', array_keys(AbilityService::ABILITIES)),
            'difficulty' => 'required|string|in:easy,medium,hard,expert,aggressive,defensive',
        ]);

        $game = Game::create([
            'board_state' => ChessBoardService::INITIAL_FEN,
            'current_turn' => 'white',
            'status' => 'active',
            'player_ability_bar' => 0,
            'ai_ability_bar' => 0,
            'player_ability' => $validated['player_ability'],
            'ai_ability' => $this->abilityService->getRandomAbility(),
            'difficulty' => $validated['difficulty'],
        ]);

        return response()->json([
            'game' => $game->load('moveHistory'),
            'difficulty' => $game->difficulty,
            'en_passant' => $this->chessService->parseFen($game->board_state)['en_passant']
        ]);
    }

    /**
     * Get available abilities.
     */
    public function abilities()
    {
        return response()->json($this->abilityService->getAvailableAbilities());
    }

    /**
     * Get game status.
     */
    public function show(Game $game)
    {
        return response()->json([
            'game' => $game->load('moveHistory'),
            'difficulty' => $game->difficulty,
            'en_passant' => $this->chessService->parseFen($game->board_state)['en_passant']
        ]);
    }

    /**
     * Handle player move.
     */
    public function move(Request $request, Game $game)
    {
        $validated = $request->validate([
            'from' => 'required|string',
            'to' => 'required|string',
            'promotion' => 'nullable|string|in:q,r,b,n',
            'use_ability' => 'boolean',
        ]);

        if ($game->status !== 'active' || $game->current_turn !== 'white') {
            return response()->json(['message' => 'Not your turn or game ended'], 403);
        }

        $useAbility = $validated['use_ability'] ?? false && $game->player_ability_bar === 100;

        // Validation
        if (!$this->chessService->isValidMove($game->board_state, $validated, $useAbility, $game->player_ability)) {
            return response()->json(['message' => 'Invalid Move'], 422);
        }

        if ($useAbility) {
            ResolveAbilityEffect::dispatch($game, $validated, 'player');
            return response()->json([
                'message' => 'Ability move queued',
                'game' => $game->fresh()
            ]);
        }

        // Standard Move Logic
        $transactionResult = DB::transaction(function () use ($game, $validated) {
            $oldFen = $game->board_state;
            $result = $this->chessService->applyMove($oldFen, $validated);
            $notation = $this->chessService->generateAlgebraic($oldFen, $result, $validated);
            
            // Record Move
            Move::create([
                'game_id' => $game->id,
                'turn_number' => $game->moves()->count() + 1,
                'piece' => $result['piece'],
                'from_square' => $validated['from'],
                'to_square' => $validated['to'],
                'captured_piece' => $result['captured_piece'],
                'fen_after' => $result['fen'],
                'notation' => $notation,
            ]);

            // Update Capture Effects via Event
            if ($result['captured_piece']) {
                PieceCaptured::dispatch($game, $result['captured_piece'], $this->chessService->getPieceColor($result['captured_piece']));
            }

            $game->recordMove('w');
            $game->board_state = $result['fen'];
            $game->current_turn = 'black';

            // Check for Game Over (Win/Draw)
            if ($this->chessService->isCheckmate($result['fen'])) {
                $game->status = 'checkmate';
            } elseif ($this->chessService->isStalemate($result['fen'])) {
                $game->status = 'draw (stalemate)';
            } elseif ($this->chessService->isFiftyMoveRule($result['fen'])) {
                $game->status = 'draw (50-move rule)';
            } elseif ($this->chessService->isInsufficientMaterial($result['fen'])) {
                $game->status = 'draw (insufficient material)';
            } else {
                // Check Threefold Repetition
                $history = $game->moves()->pluck('fen_after')->map(function($fen) {
                    return $this->chessService->getPositionKey($fen);
                })->toArray();
                $history[] = $this->chessService->getPositionKey($result['fen']);
                
                if ($this->chessService->isThreefoldRepetition($history)) {
                    $game->status = 'draw (threefold repetition)';
                }
            }

            $game->save();

            // Fire core events
            MoveProcessed::dispatch($game, 'white', $validated['from'], $validated['to'], $notation);
            
            if ($game->status !== 'active') {
                GameEnded::dispatch($game);
            }

            return $notation;
        });

        return response()->json([
            'message' => 'Move successful',
            'game' => $game->fresh(),
            'notation' => $transactionResult,
            'en_passant' => $this->chessService->parseFen($game->board_state)['en_passant']
        ]);
    }

    /**
     * Handle player resignation.
     */
    public function resign(Game $game)
    {
        if ($game->status !== 'active') {
            return response()->json(['message' => 'Game already ended'], 422);
        }

        $game->status = 'resigned';
        $game->save();

        GameEnded::dispatch($game);

        return response()->json([
            'message' => 'You have resigned.',
            'game' => $game->fresh()
        ]);
    }

    /**
     * Handle draw offer from player.
     */
    public function drawOffer(Game $game)
    {
        if ($game->status !== 'active') {
            return response()->json(['message' => 'Game already ended'], 422);
        }

        // AI Heuristic to accept draw:
        // Board evaluation is close to 0 (-15 to 15 centipawns)
        $evaluation = $this->chessService->evaluateBoard($game->board_state, $game->difficulty);
        
        $accepted = (abs($evaluation) < 15);

        if ($accepted) {
            $game->status = 'draw (accepted)';
            $game->save();
            GameEnded::dispatch($game);
            return response()->json([
                'accepted' => true,
                'message' => 'AI accepted the draw offer.',
                'game' => $game->fresh()
            ]);
        }

        return response()->json([
            'accepted' => false,
            'message' => 'AI declined the draw offer.'
        ]);
    }
}
