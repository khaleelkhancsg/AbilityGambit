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

class CalculateAiMove implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $game;
    protected $chessService;
    protected $transpositionTable = [];

    public function __construct(Game $game)
    {
        $this->game = $game;
        $this->chessService = new ChessBoardService();
    }

    public function handle(): void
    {
        if ($this->game->status !== 'active') return;
        if ($this->game->current_turn !== 'black') return;

        $abilityKey = $this->game->ai_ability;
        $barFull = ($this->game->ai_ability_bar === 100);
        $allLegalMoves = $this->chessService->generateLegalMoves($this->game->board_state, $barFull, $abilityKey);

        if (empty($allLegalMoves)) {
            $this->game->update(['status' => $this->chessService->isInCheck($this->game->board_state, 'b') ? 'checkmate' : 'draw']);
            if ($this->game->status !== 'active') GameEnded::dispatch($this->game);
            return;
        }

        $oldFen = $this->game->board_state;
        $depth = match($this->game->difficulty) {
            'easy' => 2,
            'medium' => 3,
            'hard' => 4,
            'expert' => 4, // Capping at 4 for PHP performance
            default => 3
        };
        $bestMove = $this->findBestMove($oldFen, $depth, $barFull, $abilityKey);
        $result = $this->chessService->applyMove($oldFen, $bestMove);
        $notation = $this->chessService->generateAlgebraic($oldFen, $result, $bestMove);
        
        $isAbilityUsed = false;
        if ($barFull) {
            if ($abilityKey === 'super_pawn' && strtolower($result['piece']) === 'p') {
                $from = $this->squareToCoords($bestMove['from']);
                $to = $this->squareToCoords($bestMove['to']);
                if ($from['col'] === $to['col'] && abs($from['row'] - $to['row']) === 1 && $result['captured_piece']) {
                    $isAbilityUsed = true;
                }
            } elseif ($abilityKey === 'teleport' && strtolower($result['piece']) === 'n') {
                $isAbilityUsed = true;
            }
        }

        Move::create([
            'game_id' => $this->game->id,
            'turn_number' => $this->game->moves()->count() + 1,
            'piece' => $result['piece'],
            'from_square' => $bestMove['from'],
            'to_square' => $bestMove['to'],
            'captured_piece' => $result['captured_piece'],
            'fen_after' => $result['fen'],
            'notation' => $notation,
        ]);

        if ($isAbilityUsed) {
            AbilityLog::create([
                'game_id' => $this->game->id,
                'player_type' => 'ai',
                'ability_name' => $this->game->ai_ability,
                'turn_used' => $this->game->moves()->count(),
            ]);
            $this->game->ai_ability_bar = 0;
        }

        if ($result['captured_piece']) {
            PieceCaptured::dispatch($this->game, $result['captured_piece'], $this->chessService->getPieceColor($result['captured_piece']));
        }

        $this->game->recordMove('b');
        $this->game->board_state = $result['fen'];
        $this->game->current_turn = 'white';
        
        if ($this->chessService->isCheckmate($result['fen'])) {
            $this->game->status = 'checkmate';
        } elseif ($this->chessService->isStalemate($result['fen'])) {
            $this->game->status = 'draw (stalemate)';
        } elseif ($this->chessService->isFiftyMoveRule($result['fen'])) {
            $this->game->status = 'draw (50-move rule)';
        } elseif ($this->chessService->isInsufficientMaterial($result['fen'])) {
            $this->game->status = 'draw (insufficient material)';
        } else {
            $history = [ $this->chessService->getPositionKey(\App\Services\ChessBoardService::INITIAL_FEN) ];
            $history = array_merge($history, $this->game->moves()->orderBy('id')->pluck('fen_after')->map(fn($fen) => $this->chessService->getPositionKey($fen))->toArray());
            
            if ($this->chessService->isThreefoldRepetition($history)) {
                $this->game->status = 'draw (threefold repetition)';
            }
        }

        $this->game->save();

        MoveProcessed::dispatch($this->game, 'black', $bestMove['from'], $bestMove['to'], $notation);
        if ($this->game->status !== 'active') {
            GameEnded::dispatch($this->game);
        }
    }

    private function findBestMove(string $fen, int $depth, bool $abilityActive, ?string $abilityKey): array
    {
        $moves = $this->chessService->generateLegalMoves($fen, $abilityActive, $abilityKey);
        $moves = $this->orderMoves($fen, $moves);
        
        $bestValue = 10000;
        $bestMove = $moves[0];

        foreach ($moves as $move) {
            $res = $this->chessService->applyMove($fen, $move);
            $boardValue = $this->minimax($res['fen'], $depth - 1, -10000, 10000, true);
            if ($boardValue < $bestValue) {
                $bestValue = $boardValue;
                $bestMove = $move;
            }
        }
        return $bestMove;
    }

    private function orderMoves(string $fen, array $moves): array
    {
        // Simple move ordering: captures and checks first
        usort($moves, function($a, $b) use ($fen) {
            $scoreA = 0; $scoreB = 0;
            $resA = $this->chessService->applyMove($fen, $a);
            $resB = $this->chessService->applyMove($fen, $b);
            
            if ($resA['captured_piece']) $scoreA += 100 + $this->chessService->getPieceValue($resA['captured_piece']);
            if ($resB['captured_piece']) $scoreB += 100 + $this->chessService->getPieceValue($resB['captured_piece']);
            
            if ($this->chessService->isInCheck($resA['fen'], 'w')) $scoreA += 50;
            if ($this->chessService->isInCheck($resB['fen'], 'w')) $scoreB += 50;
            
            return $scoreB <=> $scoreA;
        });
        return $moves;
    }

    private function minimax(string $fen, int $depth, int $alpha, int $beta, bool $isMaximizingPlayer): int
    {
        $hash = $fen . '|' . $depth . '|' . $isMaximizingPlayer;
        if (isset($this->transpositionTable[$hash])) return $this->transpositionTable[$hash];

        if ($depth === 0) return $this->chessService->evaluateBoard($fen, $this->game->difficulty);
        
        $moves = $this->chessService->generateLegalMoves($fen);
        if (empty($moves)) return $this->chessService->evaluateBoard($fen, $this->game->difficulty);

        if ($isMaximizingPlayer) {
            $bestVal = -10000;
            foreach ($moves as $move) {
                $res = $this->chessService->applyMove($fen, $move);
                $bestVal = max($bestVal, $this->minimax($res['fen'], $depth - 1, $alpha, $beta, false));
                $alpha = max($alpha, $bestVal);
                if ($beta <= $alpha) break;
            }
            return $this->transpositionTable[$hash] = $bestVal;
        } else {
            $bestVal = 10000;
            foreach ($moves as $move) {
                $res = $this->chessService->applyMove($fen, $move);
                $bestVal = min($bestVal, $this->minimax($res['fen'], $depth - 1, $alpha, $beta, true));
                $beta = min($beta, $bestVal);
                if ($beta <= $alpha) break;
            }
            return $this->transpositionTable[$hash] = $bestVal;
        }
    }

    private function squareToCoords(string $square): array
    {
        return ['row' => 8 - (int)$square[1], 'col' => ord($square[0]) - ord('a')];
    }
}
