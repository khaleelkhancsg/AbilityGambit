<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\ReplayController;
use Illuminate\Support\Facades\Route;

Route::get('/abilities', [GameController::class, 'abilities']);
Route::post('/games', [GameController::class, 'store']);
Route::get('/games/{game}', [GameController::class, 'show']);
Route::post('/games/{game}/move', [GameController::class, 'move']);
Route::post('/games/{game}/resign', [GameController::class, 'resign']);
Route::post('/games/{game}/draw-offer', [GameController::class, 'drawOffer']);
Route::get('/games/{game}/replay', [ReplayController::class, 'show']);
Route::get('/games/{game}/export-pgn', [ReplayController::class, 'exportPgn']);
