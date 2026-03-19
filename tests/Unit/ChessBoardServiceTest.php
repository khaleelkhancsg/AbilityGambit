<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ChessBoardService;

class ChessBoardServiceTest extends TestCase
{
    protected $chessService;

    protected function setUp(): void
    {
        $this->chessService = new ChessBoardService();
    }

    /** @test */
    public function it_validates_standard_pawn_moves()
    {
        $fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
        
        // White pawn e2 to e4 (2 squares)
        $this->assertTrue($this->chessService->isValidMove($fen, ['from' => 'e2', 'to' => 'e4']));
        
        // White pawn e2 to e3 (1 square)
        $this->assertTrue($this->chessService->isValidMove($fen, ['from' => 'e2', 'to' => 'e3']));
        
        // Invalid: White pawn e2 to e5
        $this->assertFalse($this->chessService->isValidMove($fen, ['from' => 'e2', 'to' => 'e5']));
    }

    /** @test */
    public function it_validates_super_pawn_attack_forward()
    {
        // Setup a FEN where a black pawn is directly in front of a white pawn
        // White pawn on e4, Black pawn on e5
        $fen = 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 1';
        
        // Standard rule: Cannot capture forward
        $this->assertFalse($this->chessService->isValidMove($fen, ['from' => 'e4', 'to' => 'e5'], false));
        
        // Super Pawn rule: CAN capture forward
        $this->assertTrue($this->chessService->isValidMove($fen, ['from' => 'e4', 'to' => 'e5'], true, 'super_pawn'));
    }

    /** @test */
    public function it_handles_pawn_promotion()
    {
        // White pawn on a7, white's turn. Moving to a8 should promote it.
        $fen = '8/P7/8/8/8/8/8/8 w - - 0 1';
        $move = ['from' => 'a7', 'to' => 'a8', 'promotion' => 'q'];
        
        $result = $this->chessService->applyMove($fen, $move);
        
        // Piece at a8 should be a Queen ('Q')
        $state = explode(' ', $result['fen']);
        $this->assertStringContainsString('Q', $state[0]);
        $this->assertEquals('Q', $result['piece']);
    }

    /** @test */
    public function it_detects_checkmate()
    {
        // Fool's mate FEN
        $fen = 'rnb1kbnr/pppp1ppp/8/4p3/6P1/5P2/PPPPP2P/RNBQKBNR b KQkq - 0 3';
        // After Qh4#
        $checkmateFen = 'rnb1kbnr/pppp1ppp/8/4p3/6Pq/5P2/PPPPP2P/RNBQKBNR w KQkq - 1 4';
        
        $this->assertTrue($this->chessService->isCheckmate($checkmateFen));
    }
}
