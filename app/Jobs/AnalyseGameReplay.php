<?php

namespace App\Jobs;

use App\Models\Game;
use App\Models\Move;
use App\Services\ChessBoardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyseGameReplay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $game;
    protected $chessService;

    const SCORE_WEIGHTS = [
        'p' => 1, 'n' => 3, 'b' => 3, 'r' => 5, 'q' => 9, 'k' => 100
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(Game $game)
    {
        $this->game = $game;
        $this->chessService = new ChessBoardService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $moves = $this->game->moves()->orderBy('turn_number', 'asc')->get();
        $currentFen = ChessBoardService::INITIAL_FEN;

        foreach ($moves as $move) {
            $analysis = $this->evaluateMove($currentFen, $move);
            $move->update(['analysis' => json_encode($analysis)]);
            
            // Advance FEN for next move analysis
            $currentFen = $move->fen_after;
        }
    }

    /**
     * Evaluate a specific move and look for blunders/better suggestions.
     */
    private function evaluateMove(string $fen, Move $move): array
    {
        $allLegalMoves = $this->chessService->generateLegalMoves($fen);
        $actualMoveScore = $move->captured_piece ? $this->getPieceValue($move->captured_piece) : 0;
        
        $bestScore = -1;
        $bestAlternative = null;

        foreach ($allLegalMoves as $alt) {
            $res = $this->chessService->applyMove($fen, $alt);
            $score = $res['captured_piece'] ? $this->getPieceValue($res['captured_piece']) : 0;
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestAlternative = $alt;
            }
        }

        $isBlunder = ($bestScore > $actualMoveScore + 2); // Blunder if missed a capture worth > 2 points

        return [
            'is_blunder' => $isBlunder,
            'suggested_move' => $bestAlternative,
            'suggestion_benefit' => $bestScore - $actualMoveScore
        ];
    }

    private function getPieceValue(string $piece): int
    {
        return self::SCORE_WEIGHTS[strtolower($piece)] ?? 0;
    }
}
