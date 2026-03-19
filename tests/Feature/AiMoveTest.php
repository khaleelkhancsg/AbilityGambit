<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Jobs\CalculateAiMove;
use App\Services\ChessBoardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiMoveTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ai_selects_move_that_captures_most_valuable_piece()
    {
        // FEN setup: Black (AI) has a Queen that can capture a White Rook or a White Pawn
        // We place them on row 3 (rank 6) to avoid immediate promotion issues
        // Black Queen on a8 (0,0)
        // White Rook on a3 (5,0)
        // White Pawn on b7 (1,1) -> Moved to b3 (5,1)
        $fen = 'q7/8/8/8/8/RP6/8/8 b - - 0 1';
        
        $game = Game::create([
            'board_state' => $fen,
            'current_turn' => 'black',
            'status' => 'active',
            'ai_ability_bar' => 0,
            'ai_ability' => 'super_pawn'
        ]);

        $job = new CalculateAiMove($game);
        $job->handle();

        $game->refresh();
        
        // AI should have captured the Rook (a3) rather than the Pawn (b3)
        $lastMove = $game->moves()->latest()->first();
        $this->assertEquals('a3', $lastMove->to_square);
        $this->assertEquals('R', $lastMove->captured_piece);
    }

    /** @test */
    public function ai_uses_super_pawn_when_bar_is_100()
    {
        // FEN setup: Black (AI) pawn on d5, White pawn on d4 (directly in front)
        $fen = '8/8/8/3p4/3P4/8/8/8 b - - 0 1';
        
        $game = Game::create([
            'board_state' => $fen,
            'current_turn' => 'black',
            'status' => 'active',
            'ai_ability_bar' => 100,
            'ai_ability' => 'super_pawn'
        ]);

        $job = new CalculateAiMove($game);
        $job->handle();

        $game->refresh();
        
        // AI should have used Super Pawn to capture forward
        $lastMove = $game->moves()->latest()->first();
        $this->assertEquals('d4', $lastMove->to_square);
        $this->assertEquals('P', $lastMove->captured_piece);
        $this->assertEquals(0, $game->ai_ability_bar);
    }
}
