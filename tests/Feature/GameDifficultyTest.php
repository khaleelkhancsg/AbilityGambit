<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameDifficultyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_start_a_game_with_difficulty()
    {
        $response = $this->postJson('/api/games', [
            'player_ability' => 'super_pawn',
            'difficulty' => 'hard'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('hard', $response->json('difficulty'));
        
        $game = Game::first();
        $this->assertEquals('hard', $game->difficulty);
        $this->assertEquals('super_pawn', $game->player_ability);
    }

    /** @test */
    public function it_requires_valid_difficulty()
    {
        $response = $this->postJson('/api/games', [
            'player_ability' => 'super_pawn',
            'difficulty' => 'insane'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['difficulty']);
    }

    /** @test */
    public function it_can_resign()
    {
        $game = Game::create([
            'board_state' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
            'current_turn' => 'white',
            'status' => 'active',
            'player_ability' => 'super_pawn',
            'ai_ability' => 'teleport',
            'difficulty' => 'medium'
        ]);

        $response = $this->postJson("/api/games/{$game->id}/resign");

        $response->assertStatus(200);
        $this->assertEquals('resigned', $game->fresh()->status);
    }

    /** @test */
    public function it_includes_en_passant_target_in_responses()
    {
        $response = $this->postJson('/api/games', [
            'player_ability' => 'super_pawn',
            'difficulty' => 'medium'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['game', 'en_passant']);
        $this->assertEquals('-', $response->json('en_passant'));
    }
}
