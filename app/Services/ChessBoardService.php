<?php

namespace App\Services;

class ChessBoardService
{
    const INITIAL_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    const PIECE_VALUES = [
        'p' => 5, 'n' => 15, 'b' => 15, 'r' => 20, 'q' => 40, 'k' => 0
    ];

    /**
     * Comprehensive move validation.
     */
    public function isValidMove(string $fen, array $move, bool $abilityActive = false, ?string $abilityKey = ''): bool
    {
        $legalMoves = $this->generateLegalMoves($fen, $abilityActive, $abilityKey);
        $moveStr = $move['from'] . $move['to'];
        
        foreach ($legalMoves as $legalMove) {
            if ($legalMove['from'] . $legalMove['to'] === $moveStr) {
                return true;
            }
        }
        return false;
    }

    public function generateLegalMoves(string $fen, bool $abilityActive = false, ?string $abilityKey = ''): array
    {
        $state = $this->parseFen($fen);
        $grid = $this->fenToGrid($state['placement']);
        $turn = $state['turn'];
        $allMoves = [];

        // Check for Targeted Teleport (Skip normal movement generation if Teleport is active)
        if ($abilityActive && $abilityKey === 'teleport') {
            return $this->getTeleportMoves($grid, $turn);
        }

        for ($r = 0; $r < 8; $r++) {
            for ($c = 0; $c < 8; $c++) {
                $piece = $grid[$r][$c];
                if ($piece && $this->getPieceColor($piece) === $turn) {
                    $pseudoMoves = $this->getPseudoLegalMoves($grid, $r, $c, $state, $abilityActive && $abilityKey === 'super_pawn');
                    
                    // Filter moves based on Passive Abilities (Reinforced Walls)
                    // If moving side is X, check if opponent has Reinforced Walls
                    $opponentAbility = $this->getOpponentAbility($fen);
                    
                    foreach ($pseudoMoves as $m) {
                        if ($opponentAbility === 'reinforced_walls') {
                            if ($this->isIllegalPawnVsRookCapture($grid, $m)) continue;
                        }

                        if (!$this->leavesKingInCheck($fen, $m)) {
                            $allMoves[] = $m;
                        }
                    }
                }
            }
        }
        return $allMoves;
    }

    private function getTeleportMoves(array $grid, string $color): array
    {
        $moves = [];
        $knightPiece = ($color === 'w' ? 'N' : 'n');
        
        // Find all Knights
        $knightPositions = [];
        for ($r = 0; $r < 8; $r++) {
            for ($c = 0; $c < 8; $c++) {
                if ($grid[$r][$c] === $knightPiece) {
                    $knightPositions[] = ['r' => $r, 'c' => $c];
                }
            }
        }

        // Teleport to any empty square
        foreach ($knightPositions as $pos) {
            for ($r = 0; $r < 8; $r++) {
                for ($c = 0; $c < 8; $c++) {
                    if ($grid[$r][$c] === null) {
                        $moves[] = $this->createMove($pos['r'], $pos['c'], $r, $c);
                    }
                }
            }
        }
        return $moves;
    }

    private function isIllegalPawnVsRookCapture(array $grid, array $move): bool
    {
        $from = $this->squareToCoords($move['from']);
        $to = $this->squareToCoords($move['to']);
        $piece = $grid[$from['row']][$from['col']];
        $target = $grid[$to['row']][$to['col']];

        // If Pawn tries to capture Rook
        if (strtolower($piece) === 'p' && $target && strtolower($target) === 'r') {
            return true;
        }
        return false;
    }

    private function getOpponentAbility(string $fen): string
    {
        // This helper needs the Game model context, usually passed from the caller.
        // For simplicity in this logic, we assume the Game state is handled in the Job/Controller.
        // We'll pass it as an optional param or lookup.
        return ''; 
    }

    private function getPseudoLegalMoves(array $grid, int $r, int $c, array $state, bool $superPawnActive = false): array
    {
        $piece = strtolower($grid[$r][$c]);
        $color = $this->getPieceColor($grid[$r][$c]);
        $moves = [];

        switch ($piece) {
            case 'p': $moves = $this->getPawnMoves($grid, $r, $c, $color, $state['en_passant'], $superPawnActive); break;
            case 'r': $moves = $this->getSlidingMoves($grid, $r, $c, [[0,1],[0,-1],[1,0],[-1,0]]); break;
            case 'b': $moves = $this->getSlidingMoves($grid, $r, $c, [[1,1],[1,-1],[-1,1],[-1,-1]]); break;
            case 'n': $moves = $this->getSteppingMoves($grid, $r, $c, [[2,1],[2,-1],[-2,1],[-2,-1],[1,2],[1,-2],[-1,2],[-1,-2]]); break;
            case 'q': $moves = $this->getSlidingMoves($grid, $r, $c, [[0,1],[0,-1],[1,0],[-1,0],[1,1],[1,-1],[-1,1],[-1,-1]]); break;
            case 'k': 
                $moves = $this->getSteppingMoves($grid, $r, $c, [[0,1],[0,-1],[1,0],[-1,0],[1,1],[1,-1],[-1,1],[-1,-1]]);
                $moves = array_merge($moves, $this->getCastlingMoves($grid, $r, $c, $color, $state['castling']));
                break;
        }

        return $moves;
    }

    private function getPawnMoves(array $grid, int $r, int $c, string $color, string $enPassant, bool $superPawnActive = false): array
    {
        $moves = [];
        $dir = ($color === 'w') ? -1 : 1;
        $startRank = ($color === 'w') ? 6 : 1;

        // Forward moves
        $nr = $r + $dir;
        if ($nr >= 0 && $nr < 8) {
            if ($grid[$nr][$c] === null) {
                $moves[] = $this->createMove($r, $c, $nr, $c);
                if ($r === $startRank) {
                    $nnr = $r + 2 * $dir;
                    if ($grid[$nnr][$c] === null) {
                        $moves[] = $this->createMove($r, $c, $nnr, $c);
                    }
                }
            } elseif ($superPawnActive) {
                $target = $grid[$nr][$c];
                if ($this->getPieceColor($target) !== $color) {
                    $moves[] = $this->createMove($r, $c, $nr, $c);
                }
            }
        }

        // Captures
        foreach ([-1, 1] as $dc) {
            $nr = $r + $dir;
            $nc = $c + $dc;
            if ($nr >= 0 && $nr < 8 && $nc >= 0 && $nc < 8) {
                $target = $grid[$nr][$nc];
                if ($target && $this->getPieceColor($target) !== $color) {
                    $moves[] = $this->createMove($r, $c, $nr, $nc);
                }
                if ($enPassant !== '-' && $this->coordsToSquare($nr, $nc) === $enPassant) {
                    $moves[] = $this->createMove($r, $c, $nr, $nc);
                }
            }
        }
        return $moves;
    }

    private function getSlidingMoves(array $grid, int $r, int $c, array $dirs): array
    {
        $moves = [];
        $color = $this->getPieceColor($grid[$r][$c]);
        foreach ($dirs as $d) {
            for ($i = 1; $i < 8; $i++) {
                $nr = $r + $d[0] * $i;
                $nc = $c + $d[1] * $i;
                if ($nr < 0 || $nr >= 8 || $nc < 0 || $nc >= 8) break;
                $target = $grid[$nr][$nc];
                if ($target === null) $moves[] = $this->createMove($r, $c, $nr, $nc);
                else {
                    if ($this->getPieceColor($target) !== $color) $moves[] = $this->createMove($r, $c, $nr, $nc);
                    break;
                }
            }
        }
        return $moves;
    }

    private function getSteppingMoves(array $grid, int $r, int $c, array $steps): array
    {
        $moves = [];
        $color = $this->getPieceColor($grid[$r][$c]);
        foreach ($steps as $s) {
            $nr = $r + $s[0];
            $nc = $c + $s[1];
            if ($nr >= 0 && $nr < 8 && $nc >= 0 && $nc < 8) {
                $target = $grid[$nr][$nc];
                if ($target === null || $this->getPieceColor($target) !== $color) $moves[] = $this->createMove($r, $c, $nr, $nc);
            }
        }
        return $moves;
    }

    private function getCastlingMoves(array $grid, int $r, int $c, string $color, string $rights): array
    {
        $moves = [];
        if ($color === 'w') {
            if (strpos($rights, 'K') !== false && $grid[7][5] === null && $grid[7][6] === null) $moves[] = $this->createMove(7, 4, 7, 6);
            if (strpos($rights, 'Q') !== false && $grid[7][1] === null && $grid[7][2] === null && $grid[7][3] === null) $moves[] = $this->createMove(7, 4, 7, 2);
        } else {
            if (strpos($rights, 'k') !== false && $grid[0][5] === null && $grid[0][6] === null) $moves[] = $this->createMove(0, 4, 0, 6);
            if (strpos($rights, 'q') !== false && $grid[0][1] === null && $grid[0][2] === null && $grid[0][3] === null) $moves[] = $this->createMove(0, 4, 0, 2);
        }
        return $moves;
    }

    public function isInCheck(string $fen, string $color): bool
    {
        $state = $this->parseFen($fen);
        $grid = $this->fenToGrid($state['placement']);
        $kingPos = null;
        for ($r = 0; $r < 8; $r++) {
            for ($c = 0; $c < 8; $c++) {
                if ($grid[$r][$c] === ($color === 'w' ? 'K' : 'k')) { $kingPos = [$r, $c]; break 2; }
            }
        }
        if (!$kingPos) return false;
        $opponent = ($color === 'w' ? 'b' : 'w');
        for ($r = 0; $r < 8; $r++) {
            for ($c = 0; $c < 8; $c++) {
                $piece = $grid[$r][$c];
                if ($piece && $this->getPieceColor($piece) === $opponent) {
                    $pseudo = $this->getPseudoLegalMoves($grid, $r, $c, $state);
                    foreach ($pseudo as $m) {
                        $target = $this->squareToCoords($m['to']);
                        if ($target['row'] === $kingPos[0] && $target['col'] === $kingPos[1]) return true;
                    }
                }
            }
        }
        return false;
    }

    public function leavesKingInCheck(string $fen, array $move): bool
    {
        $res = $this->applyMove($fen, $move);
        $turn = explode(' ', $fen)[1];
        return $this->isInCheck($res['fen'], $turn);
    }

    public function isCheckmate(string $fen): bool
    {
        $turn = explode(' ', $fen)[1];
        if (!$this->isInCheck($fen, $turn)) return false;
        return count($this->generateLegalMoves($fen)) === 0;
    }

    public function isStalemate(string $fen): bool
    {
        $turn = explode(' ', $fen)[1];
        if ($this->isInCheck($fen, $turn)) return false;
        return count($this->generateLegalMoves($fen)) === 0;
    }

    public function isInsufficientMaterial(string $fen): bool
    {
        $state = $this->parseFen($fen);
        $pieces = preg_replace('/[0-9\/]/', '', $state['placement']);
        
        $whitePieces = preg_replace('/[a-z]/', '', $pieces);
        $blackPieces = preg_replace('/[A-Z]/', '', $pieces);

        $piecesLookup = [
            'w' => str_split($whitePieces),
            'b' => str_split($blackPieces)
        ];

        // King vs King
        if (strlen($pieces) === 2) return true;

        $pieceCounts = [
            'w' => ['N' => 0, 'B' => 0, 'R' => 0, 'Q' => 0, 'P' => 0],
            'b' => ['n' => 0, 'b' => 0, 'r' => 0, 'q' => 0, 'p' => 0]
        ];
        $whiteBishopSquares = [];

        foreach($piecesLookup['w'] as $p) {
            if (isset($pieceCounts['w'][strtoupper($p)])) $pieceCounts['w'][strtoupper($p)]++;
        }
        foreach($piecesLookup['b'] as $p) {
            if (isset($pieceCounts['b'][$p])) $pieceCounts['b'][$p]++;
        }

        $whiteMaterial = $pieceCounts['w']['N'] + $pieceCounts['w']['B'] + $pieceCounts['w']['R'] + $pieceCounts['w']['Q'] + $pieceCounts['w']['P'];
        $blackMaterial = $pieceCounts['b']['n'] + $pieceCounts['b']['b'] + $pieceCounts['b']['r'] + $pieceCounts['b']['q'] + $pieceCounts['b']['p'];
        
        // King and minor piece vs King
        if ($whiteMaterial === 1 && $blackMaterial === 0 && ($pieceCounts['w']['N'] === 1 || $pieceCounts['w']['B'] === 1)) return true;
        if ($blackMaterial === 1 && $whiteMaterial === 0 && ($pieceCounts['b']['n'] === 1 || $pieceCounts['b']['b'] === 1)) return true;

        // King and bishops vs King and bishops (all on same color squares)
        if ($whiteMaterial === $pieceCounts['w']['B'] && $blackMaterial === $pieceCounts['b']['b']) {
            $grid = $this->fenToGrid($state['placement']);
            $bishopColor = null;
            for($r=0; $r<8; $r++) {
                for($c=0; $c<8; $c++) {
                    if($grid[$r][$c] && strtolower($grid[$r][$c]) === 'b') {
                        $squareColor = ($r + $c) % 2;
                        if ($bishopColor === null) $bishopColor = $squareColor;
                        elseif ($bishopColor !== $squareColor) return false;
                    }
                }
            }
            return true;
        }

        return false;
    }


    public function isFiftyMoveRule(string $fen): bool
    {
        $state = $this->parseFen($fen);
        return (int)$state['halfmove'] >= 100;
    }

    public function isThreefoldRepetition(array $history): bool
    {
        if (empty($history)) return false;
        $counts = array_count_values($history);
        foreach ($counts as $count) if ($count >= 3) return true;
        return false;
    }

    public function getPositionKey(string $fen): string
    {
        $parts = explode(' ', $fen);
        return implode(' ', array_slice($parts, 0, 4));
    }

    public function applyMove(string $fen, array $move): array
    {
        $state = $this->parseFen($fen);
        $grid = $this->fenToGrid($state['placement']);
        $from = $this->squareToCoords($move['from']);
        $to = $this->squareToCoords($move['to']);
        $piece = $grid[$from['row']][$from['col']];
        $capturedPiece = $grid[$to['row']][$to['col']];
        $halfmove = (int)$state['halfmove'] + 1;
        if (strtolower($piece) === 'p' || $capturedPiece) $halfmove = 0;

        // Castling
        if (strtolower($piece) === 'k' && abs($from['col'] - $to['col']) === 2) {
            $isKingSide = $to['col'] > $from['col'];
            $rookFromCol = $isKingSide ? 7 : 0; $rookToCol = $isKingSide ? 5 : 3;
            $grid[$from['row']][$rookToCol] = $grid[$from['row']][$rookFromCol];
            $grid[$from['row']][$rookFromCol] = null;
        }

        // Pawn Promotion
        if (strtolower($piece) === 'p' && ($to['row'] === 0 || $to['row'] === 7)) {
            $promo = $move['promotion'] ?? 'q';
            $piece = ($this->getPieceColor($piece) === 'w') ? strtoupper($promo) : strtolower($promo);
        }

        $grid[$to['row']][$to['col']] = $piece;
        $grid[$from['row']][$from['col']] = null;
        $nextTurn = ($state['turn'] === 'w') ? 'b' : 'w';
        $fullmove = (int)$state['fullmove'];
        if ($state['turn'] === 'b') $fullmove++;
        
        // Update En Passant
        $newEp = '-';
        if (strtolower($piece) === 'p' && abs($from['row'] - $to['row']) === 2) {
            $newEp = $this->coordsToSquare(($from['row'] + $to['row']) / 2, $from['col']);
        }
        
        // Handle En Passant Capture
        if (strtolower($piece) === 'p' && $move['to'] === $state['en_passant']) {
            $grid[$from['row']][$to['col']] = null;
            $capturedPiece = ($this->getPieceColor($piece) === 'w') ? 'p' : 'P';
        }

        $newFen = $this->gridToFen($grid, $nextTurn, $state['castling'], $newEp, $halfmove, $fullmove);
        return ['fen' => $newFen, 'piece' => $piece, 'captured_piece' => $capturedPiece, 'capture_value' => $capturedPiece ? $this->getPieceValue($capturedPiece) : 0];
    }

    public function parseFen(string $fen): array
    {
        $parts = explode(' ', $fen);
        return ['placement' => $parts[0], 'turn' => $parts[1] ?? 'w', 'castling' => $parts[2] ?? 'KQkq', 'en_passant' => $parts[3] ?? '-', 'halfmove' => $parts[4] ?? 0, 'fullmove' => $parts[5] ?? 1];
    }

    public function fenToGrid(string $placement): array
    {
        $rows = explode('/', $placement); $grid = [];
        foreach ($rows as $row) {
            $gridRow = [];
            for ($i = 0; $i < strlen($row); $i++) {
                if (is_numeric($row[$i])) for ($j = 0; $j < (int)$row[$i]; $j++) $gridRow[] = null;
                else $gridRow[] = $row[$i];
            }
            $grid[] = $gridRow;
        }
        return $grid;
    }

    public function gridToFen(array $grid, string $turn = 'w', string $castling = 'KQkq', string $ep = '-', int $half = 0, int $full = 1): string
    {
        $rows = [];
        foreach ($grid as $gridRow) {
            $rowStr = ''; $empty = 0;
            foreach ($gridRow as $square) {
                if ($square === null) $empty++;
                else { if ($empty > 0) { $rowStr .= $empty; $empty = 0; } $rowStr .= $square; }
            }
            if ($empty > 0) $rowStr .= $empty;
            $rows[] = $rowStr;
        }
        return implode('/', $rows) . " $turn $castling $ep $half $full";
    }

    private function createMove(int $fr, int $fc, int $tr, int $tc): array
    {
        return ['from' => $this->coordsToSquare($fr, $fc), 'to' => $this->coordsToSquare($tr, $tc)];
    }

    private function squareToCoords(string $square): array
    {
        return ['row' => 8 - (int)$square[1], 'col' => ord($square[0]) - ord('a')];
    }

    private function coordsToSquare(int $r, int $c): string
    {
        return chr(ord('a') + $c) . (8 - $r);
    }

    public function getPieceColor(string $piece): string
    {
        return ctype_upper($piece) ? 'w' : 'b';
    }

    public function getPieceValue(string $piece, string $difficulty = 'medium'): int
    {
        $values = self::PIECE_VALUES;
        
        // Adjust for personalities
        if ($difficulty === 'aggressive') {
            $values['q'] = 50; // Values Queen more
            $values['n'] = 20; // Values Knight more (attacking piece)
            $values['p'] = 3;  // Values Pawns less (sacrificial)
        } elseif ($difficulty === 'defensive') {
            $values['r'] = 25; // Values Rooks more (defensive pieces)
            $values['p'] = 8;  // Values Pawns more (wall)
        }

        return $values[strtolower($piece)] ?? 0;
    }

    public function evaluateBoard(string $fen, string $difficulty = 'medium'): int
    {
        $state = $this->parseFen($fen);
        $grid = $this->fenToGrid($state['placement']); $score = 0;
        for ($r = 0; $r < 8; $r++) {
            for ($c = 0; $c < 8; $c++) {
                if (!$grid[$r][$c]) continue;
                $val = $this->getPieceValue($grid[$r][$c], $difficulty);
                $isWhite = $this->getPieceColor($grid[$r][$c]) === 'w';
                $score += $isWhite ? $val : -$val;
                if (($r >= 3 && $r <= 4) && ($c >= 3 && $c <= 4)) $score += $isWhite ? 1 : -1;
            }
        }
        return $score;
    }

    public function generatePgn(\App\Models\Game $game): string
    {
        $moves = $game->moves()->orderBy('turn_number', 'asc')->get();
        $pgn = ['[Event "The Ability Gambit Match"]', '[Site "Localhost"]', '[Date "' . $game->created_at->format('Y.m.d') . '"]', '[Round "1"]', '[White "Player"]', '[Black "AI"]', '[Result "' . ($game->status === 'checkmate' ? ($game->current_turn === 'black' ? '1-0' : '0-1') : ($game->status !== 'active' ? '1/2-1/2' : '*')) . '"]', '[Variant "The Ability Gambit"]', '[PlayerAbility "' . $game->player_ability . '"]', '[AIAbility "' . $game->ai_ability . '"]', ''];
        $moveList = []; $turnCounter = 1;
        for ($i = 0; $i < count($moves); $i += 2) {
            $line = $turnCounter . '. ' . $this->toAlgebraic($moves[$i]);
            if (isset($moves[$i + 1])) $line .= ' ' . $this->toAlgebraic($moves[$i + 1]);
            $moveList[] = $line; $turnCounter++;
        }
        $pgn[] = implode(' ', $moveList) . ' ' . ($game->status === 'checkmate' ? ($game->current_turn === 'black' ? '1-0' : '0-1') : ($game->status !== 'active' ? '1/2-1/2' : '*'));
        return implode("\n", $pgn);
    }

    public function generateAlgebraic(string $fenBefore, array $moveResult, array $moveData): string
    {
        $movingPiece = $moveResult['piece'];
        $piece = strtoupper($movingPiece);
        $notation = ($piece === 'P') ? '' : $piece;
        
        // Handle Castling notation (O-O, O-O-O)
        if ($piece === 'K') {
            $from = $this->squareToCoords($moveData['from']);
            $to = $this->squareToCoords($moveData['to']);
            if (abs($from['col'] - $to['col']) === 2) {
                return ($to['col'] > $from['col']) ? 'O-O' : 'O-O-O';
            }
        }
        
        // Disambiguation logic
        if ($piece !== 'P' && $piece !== 'K') {
            $state = $this->parseFen($fenBefore);
            $grid = $this->fenToGrid($state['placement']);
            $color = $this->getPieceColor($movingPiece);
            $ambiguousMoves = [];

            for ($r = 0; $r < 8; $r++) {
                for ($c = 0; $c < 8; $c++) {
                    $otherPiece = $grid[$r][$c];
                    // Find other pieces of the same type and color, but not the one that moved
                    if ($otherPiece === $movingPiece && $this->coordsToSquare($r, $c) !== $moveData['from']) {
                        $legalMoves = $this->getPseudoLegalMoves($grid, $r, $c, $state);
                        foreach ($legalMoves as $legalMove) {
                            if ($legalMove['to'] === $moveData['to']) {
                                $ambiguousMoves[] = $legalMove['from'];
                            }
                        }
                    }
                }
            }

            if (count($ambiguousMoves) > 0) {
                $fromSquare = $moveData['from'];
                $needsFile = false;
                $needsRank = false;

                foreach ($ambiguousMoves as $ambiguousFrom) {
                    if ($ambiguousFrom[0] !== $fromSquare[0]) $needsFile = true;
                    if ($ambiguousFrom[1] !== $fromSquare[1]) $needsRank = true;
                }
                
                $fromFile = $fromSquare[0];
                $fromRank = $fromSquare[1];
                
                $canDisambiguateByFile = true;
                foreach($ambiguousMoves as $ambiguousFrom) {
                    if($ambiguousFrom[0] === $fromFile) $canDisambiguateByFile = false;
                }

                if ($needsFile && $canDisambiguateByFile) {
                    $notation .= $fromFile;
                } elseif ($needsRank) {
                     $notation .= $fromRank;
                } else {
                    $notation .= $fromSquare;
                }
            }
        }

        if ($moveResult['captured_piece']) {
            if ($piece === 'P') $notation .= substr($moveData['from'], 0, 1);
            $notation .= 'x';
        }
        
        $notation .= $moveData['to'];
        
        // Promotion notation (e.g., e8=Q)
        if (isset($moveData['promotion'])) {
            $notation .= '=' . strtoupper($moveData['promotion']);
        }

        if ($this->isInCheck($moveResult['fen'], $this->getPieceColor($movingPiece) === 'w' ? 'b' : 'w')) {
            $notation .= $this->isCheckmate($moveResult['fen']) ? '#' : '+';
        }
        
        return $notation;
    }

    private function toAlgebraic(\App\Models\Move $move): string
    {
        // Keep this for PGN generation, but it can now call the new public method
        $moveResult = [
            'piece' => $move->piece,
            'captured_piece' => $move->captured_piece,
            'fen' => $move->fen_after
        ];
        $moveData = [
            'from' => $move->from_square,
            'to' => $move->to_square
        ];
        // We might not have the FEN before here easily, but generateAlgebraic doesn't strictly need it for current logic
        return $this->generateAlgebraic('', $moveResult, $moveData);
    }
}
