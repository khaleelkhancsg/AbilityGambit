<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\ChessBoardService;
use Illuminate\Http\Request;

class ReplayController extends Controller
{
    protected $chessService;

    public function __construct()
    {
        $this->chessService = new ChessBoardService();
    }

    /**
     * Get all moves for a game to reconstruct the replay.
     */
    public function show(Game $game)
    {
        $moves = $game->moves()->orderBy('turn_number', 'asc')->get();
        
        return response()->json([
            'game' => $game,
            'moves' => $moves
        ]);
    }

    /**
     * Export game as PGN file.
     */
    public function exportPgn(Game $game)
    {
        $pgn = $this->chessService->generatePgn($game);
        $filename = "game_{$game->id}.pgn";

        return response($pgn)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
