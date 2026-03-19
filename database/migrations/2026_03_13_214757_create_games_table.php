<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->text('board_state'); // FEN string
            $table->string('current_turn')->default('white');
            $table->integer('player_ability_bar')->default(0);
            $table->integer('ai_ability_bar')->default(0);
            $table->integer('player_move_count')->default(0);
            $table->integer('ai_move_count')->default(0);
            $table->string('status')->default('active'); // active, checkmate, draw, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
