<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameAbilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_increases_player_bar_every_5_moves()
    {
        $game = Game::create([
            'board_state' => '8/8/8/8/8/8/8/8 w KQkq - 0 1',
            'player_move_count' => 4,
            'player_ability_bar' => 0
        ]);

        $game->recordMove('w'); // 5th move

        $this->assertEquals(5, $game->player_ability_bar);
        $this->assertEquals(5, $game->player_move_count);
    }

    /** @test */
    public function it_caps_ability_bar_at_100()
    {
        $game = Game::create([
            'board_state' => '8/8/8/8/8/8/8/8 w KQkq - 0 1',
            'player_ability_bar' => 100
        ]);

        $game->recordCapture('w'); // Player piece taken, should increase bar

        $this->assertEquals(100, $game->player_ability_bar);
    }

    /** @test */
    public function it_updates_bars_correctly_on_capture()
    {
        $game = Game::create([
            'board_state' => '8/8/8/8/8/8/8/8 w KQkq - 0 1',
            'player_ability_bar' => 10,
            'ai_ability_bar' => 10
        ]);

        // White (Player) piece taken
        $game->recordCapture('w');

        // Player bar increases, AI bar decreases
        $this->assertEquals(15, $game->player_ability_bar);
        $this->assertEquals(5, $game->ai_ability_bar);

        // Black (AI) piece taken
        $game->recordCapture('b');

        // AI bar increases, Player bar decreases
        $this->assertEquals(10, $game->ai_ability_bar);
        $this->assertEquals(10, $game->player_ability_bar);
    }
}
