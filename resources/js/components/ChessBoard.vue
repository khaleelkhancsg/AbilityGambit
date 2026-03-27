<template>
  <!-- 1. Improved Ability Selection with Confirmation -->
  <div v-if="view === 'selection'" class="flex flex-col items-center justify-center gap-8 p-8 bg-gray-900 min-h-screen">
    <div class="text-center space-y-2">
        <h1 class="text-5xl font-black text-white tracking-tighter uppercase italic">The Ability Gambit</h1>
        <p class="text-blue-400 font-bold tracking-widest text-xs uppercase">Choose your tactical advantage</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-[1000px]">
        <div 
            v-for="(details, key) in availableAbilities" 
            :key="key"
            @click="selectAbility(key)"
            class="p-8 bg-gray-800 border-2 rounded-3xl cursor-pointer transition-all hover:scale-105 active:scale-95 flex flex-col items-center text-center group"
            :class="selectedAbility === key ? 'border-blue-500 shadow-[0_0_30px_rgba(59,130,246,0.4)] bg-gray-700' : 'border-gray-700 hover:border-blue-400'"
        >
            <div class="text-6xl mb-6 transform group-hover:rotate-12 transition-transform">{{ details.icon }}</div>
            <h3 class="text-2xl font-black text-white mb-3 uppercase tracking-tighter">{{ details.name }}</h3>
            <p class="text-gray-400 text-sm leading-relaxed mb-6">{{ details.description }}</p>
            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                  :class="details.type === 'passive' ? 'bg-green-900 text-green-400' : 'bg-blue-900 text-blue-400'">
                {{ details.type }}
            </span>
        </div>
    </div>

    <!-- Confirmation Prompt -->
    <div v-if="selectedAbility" class="mt-8 animate-fade-in flex flex-col items-center gap-4">
        <p class="text-white text-sm font-bold">Deploy with <span class="text-blue-400 underline decoration-2">{{ availableAbilities[selectedAbility].name }}</span>?</p>
        <button 
            @click="view = 'difficulty'" 
            class="px-20 py-5 bg-blue-600 text-white font-black rounded-full shadow-2xl hover:bg-blue-500 transition-all active:scale-95 uppercase tracking-[0.2em] italic"
        >
            Next: Choose Difficulty
        </button>
    </div>
  </div>

  <!-- 2. Difficulty Selection -->
  <div v-else-if="view === 'difficulty'" class="flex flex-col items-center justify-center gap-8 p-8 bg-gray-900 min-h-screen">
    <div class="text-center space-y-2">
        <h1 class="text-5xl font-black text-white tracking-tighter uppercase italic">Select AI Persona</h1>
        <p class="text-blue-400 font-bold tracking-widest text-xs uppercase">Choose your opponent's playstyle and depth</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-[1000px]">
        <div 
            v-for="(details, key) in difficulties" 
            :key="key"
            @click="selectedDifficulty = key"
            class="p-6 bg-gray-800 border-2 rounded-3xl cursor-pointer transition-all hover:scale-105 active:scale-95 flex flex-col items-center text-center group"
            :class="selectedDifficulty === key ? 'border-blue-500 shadow-[0_0_30px_rgba(59,130,246,0.4)] bg-gray-700' : 'border-gray-700 hover:border-blue-400'"
        >
            <div class="text-5xl mb-4 transform group-hover:scale-110 transition-transform">{{ details.icon }}</div>
            <h3 class="text-xl font-black text-white mb-2 uppercase tracking-tighter">{{ details.name }}</h3>
            <p class="text-gray-400 text-xs leading-relaxed mb-4">{{ details.description }}</p>
            <div class="flex gap-1">
                <div v-for="i in 5" :key="i" class="w-2 h-2 rounded-full" :class="i <= details.level ? 'bg-blue-500' : 'bg-gray-600'"></div>
            </div>
        </div>
    </div>

    <div v-if="selectedDifficulty" class="mt-8 animate-fade-in flex flex-col items-center gap-4">
        <button 
            @click="startGame" 
            class="px-20 py-5 bg-blue-600 text-white font-black rounded-full shadow-2xl hover:bg-blue-500 transition-all active:scale-95 uppercase tracking-[0.2em] italic"
        >
            Start Match
        </button>
        <button @click="view = 'selection'" class="text-gray-500 text-xs font-bold uppercase tracking-widest hover:text-white transition-colors">Back to Abilities</button>
    </div>
  </div>

  <!-- Game View -->
  <div v-else-if="view === 'game'" 
       class="flex flex-col xl:flex-row items-center justify-center gap-12 p-8 bg-gray-50 min-h-screen w-full transition-all duration-500"
       :class="{ 'shake-animation': isShaking }"
  >
    <div class="flex flex-col items-center gap-6">
      <div class="flex justify-between w-full max-w-[512px] items-center">
          <h1 class="text-2xl font-black text-gray-800 tracking-tighter uppercase italic">The Ability Gambit</h1>
          <div class="px-3 py-1 bg-white border-2 border-gray-200 rounded-lg text-[10px] font-black uppercase tracking-widest text-gray-400">
              Game ID: #{{ game.id }}
          </div>
      </div>

      <!-- AI Info -->
      <div class="w-full max-w-[512px] space-y-2">
        <div class="flex justify-between items-end">
          <div class="flex flex-col">
              <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">{{ difficulties[game.difficulty]?.name }} AI</span>
              <span class="text-xs font-bold text-red-600 uppercase tracking-widest">Ability: {{ availableAbilities[game.ai_ability]?.name }}</span>
          </div>
          <span class="text-[10px] font-mono text-gray-400">{{ game.ai_ability_bar }}%</span>
        </div>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden border border-gray-300">
          <div 
            class="h-full bg-red-500 transition-all duration-700 ease-out" 
            :style="{ width: game.ai_ability_bar + '%' }"
            :class="{ 'glow-red': game.ai_ability_bar === 100 }"
          ></div>
        </div>
      </div>

      <!-- Board -->
      <div class="relative shadow-[0_20px_50px_rgba(0,0,0,0.1)] rounded-xl p-4 bg-gray-200">
        <div class="grid grid-cols-8 grid-rows-8 w-[512px] h-[512px] border-4 border-gray-800 bg-white shadow-inner overflow-hidden rounded-sm">
          <template v-for="(row, rIndex) in boardGrid" :key="'row-' + rIndex">
            <div 
              v-for="(piece, cIndex) in row" 
              :key="rIndex + '-' + cIndex"
              @click="handleSquareClick(rIndex, cIndex)"
              class="relative flex items-center justify-center text-4xl cursor-pointer select-none transition-all duration-200"
              :class="[
                  (rIndex + cIndex) % 2 === 0 ? 'bg-[#eeeed2]' : 'bg-[#769656]',
                  isSelected(rIndex, cIndex) ? 'bg-yellow-200 ring-4 ring-inset ring-yellow-400 z-10 scale-105' : '',
                  isLastMove(rIndex, cIndex) ? 'bg-blue-100/50 ring-4 ring-inset ring-blue-300/50' : '',
                  isTargetable(rIndex, cIndex) ? 'cursor-crosshair bg-blue-200 animate-pulse ring-4 ring-inset ring-blue-400 z-10' : ''
              ]"
            >
              <span v-if="piece" 
                    class="drop-shadow-[0_2px_2px_rgba(0,0,0,0.5)] transform transition-all duration-300 relative font-bold" 
                    :class="[
                        getPieceColorClass(piece), 
                        isJustMoved(rIndex, cIndex) ? 'animate-bounce-short' : '',
                        isPieceAffectedByAbility(piece, rIndex, cIndex) ? 'glow-ability' : '',
                        isEnPassantTarget(rIndex, cIndex) ? 'glow-enpassant' : ''
                    ]"
              >
                {{ getPieceUnicode(piece) + '\uFE0E' }}
                <span v-if="isPieceAffectedByAbility(piece, rIndex, cIndex)" 
                      class="absolute -top-2 -right-2 text-[10px] bg-yellow-400 rounded-full w-4 h-4 flex items-center justify-center border border-gray-900 shadow-sm animate-pulse"
                >
                    {{ availableAbilities[isWhitePiece(piece) ? game.player_ability : game.ai_ability]?.icon }}
                </span>
              </span>
            </div>
          </template>
        </div>

        <!-- Promotion Modal -->
        <div v-if="promotionModalVisible" class="absolute inset-0 bg-black/60 flex items-center justify-center backdrop-blur-sm rounded-sm z-40 animate-fade-in">
          <div class="bg-white p-6 rounded-2xl shadow-2xl text-center border-4 border-blue-500">
              <h3 class="text-xl font-black mb-4 uppercase italic text-gray-900 tracking-tighter">Pawn Promotion</h3>
              <div class="flex gap-4">
                  <button 
                      v-for="promo in ['q', 'r', 'b', 'n']" 
                      :key="promo"
                      @click="confirmPromotion(promo)"
                      class="w-16 h-16 flex items-center justify-center bg-gray-100 hover:bg-blue-100 border-2 border-gray-200 hover:border-blue-400 rounded-xl transition-all text-4xl shadow-md active:scale-95"
                  >
                      {{ getPieceUnicode(promo.toUpperCase()) }}
                  </button>
              </div>
              <p class="mt-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Choose your reinforced unit</p>
          </div>
        </div>

        <!-- Overlays unchanged -->
        <div v-if="game.current_turn === 'black' && game.status === 'active'" class="absolute inset-0 bg-black/10 flex items-center justify-center backdrop-blur-[1px] rounded-sm">
          <div class="bg-white px-6 py-3 rounded-full shadow-2xl flex items-center gap-3 animate-pulse">
              <div class="w-4 h-4 border-2 border-gray-800 border-t-transparent animate-spin rounded-full"></div>
              <span class="text-xs font-black text-gray-800 uppercase tracking-widest">AI Calculating...</span>
          </div>
        </div>

        <div v-if="game.status !== 'active'" class="absolute inset-0 bg-black/70 flex items-center justify-center backdrop-blur-md rounded-sm z-50 animate-fade-in">
          <div class="bg-white p-10 rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.5)] text-center">
              <h2 class="text-4xl font-black mb-2 uppercase italic text-gray-900 tracking-tighter">Game Over</h2>
              <p class="text-gray-500 mb-8 font-bold uppercase tracking-widest">{{ game.status }}</p>
              <div class="flex gap-4">
                  <button @click="view = 'selection'" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-black py-3 px-8 rounded-xl transition-all shadow-lg active:scale-95 uppercase text-sm">New Game</button>
                  <button @click="view = 'replay'" class="flex-1 bg-gray-900 hover:bg-black text-white font-black py-3 px-8 rounded-xl transition-all shadow-lg active:scale-95 uppercase text-sm">Replay</button>
              </div>
          </div>
        </div>
      </div>

      <!-- Player Info -->
      <div class="w-full max-w-[512px] space-y-4">
        <div class="flex justify-between items-center">
          <div class="flex items-center gap-3">
              <div class="text-2xl">{{ availableAbilities[game.player_ability]?.icon }}</div>
              <div class="flex flex-col">
                  <span class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] leading-tight">{{ availableAbilities[game.player_ability]?.name }}</span>
                  <span v-if="game.player_ability_bar === 100" class="text-[8px] font-black text-yellow-500 animate-pulse uppercase">Ability Ready</span>
              </div>
          </div>
          <span class="text-[10px] font-mono font-bold text-gray-400">{{ game.player_ability_bar }}%</span>
        </div>
        
        <div class="h-4 bg-gray-200 rounded-full overflow-hidden border-2 border-gray-300 shadow-inner relative">
          <div 
            class="h-full bg-blue-500 transition-all duration-500 ease-out" 
            :style="{ width: game.player_ability_bar + '%' }"
            :class="{ 'glow-blue animate-pulse': game.player_ability_bar === 100 }"
          ></div>
          
          <button 
            v-if="game.player_ability_bar === 100 && availableAbilities[game.player_ability]?.type !== 'passive'"
            @click="toggleAbility"
            class="absolute inset-0 w-full h-full flex items-center justify-center font-black text-[10px] uppercase tracking-widest text-white transition-all"
            :class="abilityActive ? 'bg-yellow-500 text-black' : 'hover:bg-white/10'"
          >
            {{ abilityActive ? '⚡ CANCEL TARGETING ⚡' : 'ACTIVATE ABILITY' }}
          </button>
        </div>

        <!-- NEW: Action Buttons (Resign/Draw) -->
        <div v-if="game.status === 'active'" class="flex gap-2 pt-2">
            <button 
                @click="offerDraw"
                class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-[10px] font-black rounded-lg transition-all uppercase tracking-widest border border-gray-300 active:scale-95"
            >
                Offer Draw
            </button>
            <button 
                @click="resignGame"
                class="flex-1 px-4 py-2 bg-red-100 hover:bg-red-200 text-red-600 text-[10px] font-black rounded-lg transition-all uppercase tracking-widest border border-red-200 active:scale-95"
            >
                Resign
            </button>
        </div>
      </div>
    </div>

    <!-- Move History -->
    <div class="w-full xl:max-w-[250px] bg-white border-2 border-gray-200 rounded-2xl shadow-lg p-4 self-stretch">
        <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest text-center mb-3">Move History</h3>
        <div ref="moveHistoryRef" class="h-[600px] xl:h-[calc(100%-30px)] overflow-y-auto pr-2 -mr-2">
            <table class="w-full text-sm text-left">
                <thead class="sticky top-0 bg-white">
                    <tr class="text-[10px] uppercase font-bold text-gray-400">
                        <th class="px-2 py-1 w-8">#</th>
                        <th class="px-2 py-1">White</th>
                        <th class="px-2 py-1">Black</th>
                    </tr>
                </thead>
                <tbody class="font-mono font-bold">
                    <tr v-for="group in groupedMoveHistory" :key="group.number" class="border-b border-gray-100 last:border-b-0">
                        <td class="px-2 py-1.5 text-gray-400">{{ group.number }}</td>
                        <td class="px-2 py-1.5 text-gray-700">{{ group.white }}</td>
                        <td class="px-2 py-1.5 text-gray-500">{{ group.black }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
  </div>

  <ReplayView v-else :game-id="game.id" @close="view = 'game'" />
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch, nextTick } from 'vue';
import axios from 'axios';
import ReplayView from './ReplayView.vue';

const view = ref('selection');
const availableAbilities = ref({});
const selectedAbility = ref(null);
const selectedDifficulty = ref('medium');
const enPassantTarget = ref('-');

const difficulties = {
    easy: { name: 'Novice', icon: '🐣', level: 1, description: 'Quick thinking but lacks foresight. (Depth 2)' },
    medium: { name: 'Intermediate', icon: '🧠', level: 2, description: 'Standard AI with solid tactical awareness. (Depth 3)' },
    hard: { name: 'Advanced', icon: '⚔️', level: 3, description: 'Calculates multiple turns ahead. (Depth 4)' },
    expert: { name: 'Expert', icon: '👑', level: 4, description: 'Deep calculation for serious challengers. (Depth 5)' },
    aggressive: { name: 'Berserker', icon: '🔥', level: 2, description: 'Values attack and material sacrifices. (Depth 3)' },
    defensive: { name: 'Guardian', icon: '🛡️', level: 2, description: 'Prioritizes safety and pawn structures. (Depth 3)' },
};

const isShaking = ref(false);
const abilityActive = ref(false);
const lastMoveTarget = ref(null);
const lastMove = ref({ from: null, to: null });
const moveHistoryRef = ref(null);

const promotionModalVisible = ref(false);
const pendingMove = ref(null);
const capturedPieces = ref({ w: [], b: [] });

const game = reactive({
  id: null, board_state: 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
  current_turn: 'white', player_ability_bar: 0, ai_ability_bar: 0, status: 'active',
  player_ability: null, ai_ability: null, difficulty: 'medium', move_history: []
});

const groupedMoveHistory = computed(() => {
    const groups = [];
    const history = game.move_history || [];
    for (let i = 0; i < history.length; i += 2) {
        groups.push({
            number: Math.floor(i / 2) + 1,
            white: history[i],
            black: history[i + 1] || null
        });
    }
    return groups;
});

// Audio & Shaking unchanged
const sounds = {
    move: new Audio('https://assets.chesscomfiles.com/chess-themes/sounds/_default/move-self.mp3'),
    capture: new Audio('https://assets.chesscomfiles.com/chess-themes/sounds/_default/capture.mp3'),
    ability: new Audio('https://assets.chesscomfiles.com/chess-themes/sounds/_default/notify.mp3'),
    gameOver: new Audio('https://assets.chesscomfiles.com/chess-themes/sounds/_default/game-end.mp3')
};
const playSound = (n) => { if (sounds[n]) { sounds[n].currentTime = 0; sounds[n].play().catch(() => {}); } };
const shakeBoard = () => { isShaking.value = true; setTimeout(() => isShaking.value = false, 500); };

const boardGrid = computed(() => {
    const placement = game.board_state.split(' ')[0];
    return placement.split('/').map(row => {
        const gridRow = [];
        for (let char of row) {
            if (!isNaN(char)) for (let i = 0; i < parseInt(char); i++) gridRow.push(null);
            else gridRow.push(char);
        }
        return gridRow;
    });
});

const selectedSquare = ref(null);
const isSelected = (r, c) => selectedSquare.value?.r === r && selectedSquare.value?.c === c;
const isLastMove = (r, c) => { const square = coordsToSquare(r, c); return lastMove.value.from === square || lastMove.value.to === square; };

// NEW: Targetable squares for abilities like Teleport
const isTargetable = (r, c) => {
    if (!abilityActive.value || !selectedSquare.value) return false;
    const ability = availableAbilities.value[game.player_ability];
    if (ability.key === 'teleport' || game.player_ability === 'teleport') {
        // Must have selected a Knight, then target any empty square
        const selectedPiece = boardGrid.value[selectedSquare.value.r][selectedSquare.value.c];
        return selectedPiece === 'N' && boardGrid.value[r][c] === null;
    }
    return false;
};

const handleSquareClick = (r, c) => {
    if (game.current_turn !== 'white' || game.status !== 'active' || promotionModalVisible.value) return;
    const squareName = coordsToSquare(r, c);
    const piece = boardGrid.value[r][c];

    if (selectedSquare.value) {
        if (selectedSquare.value.r === r && selectedSquare.value.c === c) selectedSquare.value = null;
        else makeMove(selectedSquare.value.name, squareName);
    } else if (piece && piece === piece.toUpperCase()) {
        selectedSquare.value = { r, c, name: squareName };
    }
};

const makeMove = async (from, to, promotion = null) => {
    const fromR = 8 - parseInt(from[1]);
    const fromC = from.charCodeAt(0) - 97;
    const piece = boardGrid.value[fromR][fromC];

    // Detect Promotion
    if (piece === 'P' && to.endsWith('8') && !promotion) {
        pendingMove.value = { from, to };
        promotionModalVisible.value = true;
        return;
    }

    const targetPiece = boardGrid.value[8 - parseInt(to[1])][to.charCodeAt(0) - 97];
    try {
        const response = await axios.post(`/api/games/${game.id}/move`, { 
            from, 
            to, 
            use_ability: abilityActive.value,
            promotion: promotion 
        });
        Object.assign(game, response.data.game);
        if(response.data.notation) {
            game.move_history.push(response.data.notation);
        }
        enPassantTarget.value = response.data.en_passant || '-';
        lastMove.value = { from, to };
        
        if (abilityActive.value) { playSound('ability'); shakeBoard(); }
        else if (targetPiece) playSound('capture');
        else playSound('move');

        abilityActive.value = false;
        selectedSquare.value = null;
        promotionModalVisible.value = false;
        pendingMove.value = null;
    } catch (e) {
        alert(e.response?.data?.message || 'Invalid Move');
        promotionModalVisible.value = false;
        pendingMove.value = null;
    }
};

const confirmPromotion = (piece) => {
    if (pendingMove.value) {
        makeMove(pendingMove.value.from, pendingMove.value.to, piece);
    }
};

const setupEcho = () => {
    if (!game.id) return;
    console.log('Setting up Echo for game:', game.id);
    window.Echo.channel(`game.${game.id}`).listen('MoveProcessed', (e) => {
        console.log('Echo MoveProcessed received:', e);
        if (e.side === 'black') { 
            // Update game state but don't trigger anything else
            Object.assign(game, e.game); 
            if (e.notation) {
                // Check if this move is already in history to avoid duplicates
                if (!game.move_history.includes(e.notation)) {
                    game.move_history.push(e.notation);
                }
            }
            if (e.last_move) lastMove.value = e.last_move;
            const parts = e.game.board_state.split(' ');
            enPassantTarget.value = parts[3] || '-';
            playSound('move'); 
            selectedSquare.value = null; // Clear selection after AI move
        }
    });
};

const selectAbility = (key) => { selectedAbility.value = key; playSound('move'); };
const startGame = async () => {
    try {
        const response = await axios.post('/api/games', { 
            player_ability: selectedAbility.value,
            difficulty: selectedDifficulty.value
        });
        const gameData = response.data.game;
        Object.assign(game, gameData);
        game.move_history = response.data.moveHistory ? response.data.moveHistory.map(m => m.notation) : [];
        enPassantTarget.value = response.data.en_passant || '-';
        view.value = 'game';
        playSound('move');
        setupEcho();
    } catch (error) {
        console.error("Failed to start game:", error);
        alert("Could not start a new game. Please try again.");
    }
};

const resignGame = async () => {
    if (!confirm('Are you sure you want to resign?')) return;
    try {
        const response = await axios.post(`/api/games/${game.id}/resign`);
        Object.assign(game, response.data.game);
        alert(response.data.message);
    } catch (e) {
        alert(e.response?.data?.message || 'Failed to resign');
    }
};

const offerDraw = async () => {
    try {
        const response = await axios.post(`/api/games/${game.id}/draw-offer`);
        if (response.data.accepted) {
            Object.assign(game, response.data.game);
            alert(response.data.message);
        } else {
            alert(response.data.message);
        }
    } catch (e) {
        alert(e.response?.data?.message || 'Failed to offer draw');
    }
};

const toggleAbility = () => { abilityActive.value = !abilityActive.value; playSound('move'); };

const isPieceAffectedByAbility = (p, r, c) => {
    const isWhite = isWhitePiece(p);
    const pieceType = p.toLowerCase();
    
    // Check for Player Ability
    if (isWhite) {
        const ability = game.player_ability;
        if (ability === 'super_pawn' && pieceType === 'p' && abilityActive.value) return true;
        if (ability === 'teleport' && pieceType === 'n' && abilityActive.value) return true;
        if (ability === 'reinforced_walls' && pieceType === 'r') return true; // Passive
    } else {
        // Check for AI Ability
        const ability = game.ai_ability;
        const barFull = game.ai_ability_bar === 100;
        if (ability === 'super_pawn' && pieceType === 'p' && barFull) return true;
        if (ability === 'teleport' && pieceType === 'n' && barFull) return true;
        if (ability === 'reinforced_walls' && pieceType === 'r') return true; // Passive
    }
    return false;
};

const isEnPassantTarget = (r, c) => {
    if (!selectedSquare.value || enPassantTarget.value === '-') return false;
    
    const selectedPiece = boardGrid.value[selectedSquare.value.r][selectedSquare.value.c];
    if (!selectedPiece || selectedPiece.toLowerCase() !== 'p') return false;

    // The en_passant target square is the square behind the pawn that just moved two squares
    // We want to highlight the pawn that can be captured
    const targetCoords = squareToCoords(enPassantTarget.value);
    
    // If current square (r, c) is where the capturable pawn is
    // For white pawn capturing black: EP target is behind the black pawn (rank 3, r=5)
    // The black pawn itself is on rank 4 (r=4)
    if (isWhitePiece(selectedPiece)) {
        return r === 4 && targetCoords.row === 5 && targetCoords.col === c && Math.abs(selectedSquare.value.c - c) === 1 && selectedSquare.value.r === 4;
    } else {
        // For black pawn capturing white: EP target is behind the white pawn (rank 6, r=2)
        // The white pawn itself is on rank 5 (r=3)
        return r === 3 && targetCoords.row === 2 && targetCoords.col === c && Math.abs(selectedSquare.value.c - c) === 1 && selectedSquare.value.r === 3;
    }
};

const coordsToSquare = (r, c) => String.fromCharCode(97 + c) + (8 - r);
const squareToCoords = (s) => ({ row: 8 - parseInt(s[1]), col: s.charCodeAt(0) - 97 });
const isWhitePiece = (p) => p === p.toUpperCase();
const getPieceColorClass = (p) => isWhitePiece(p) ? 'text-white' : 'text-gray-900';
const isJustMoved = (r, c) => false;
const getPieceUnicode = (p) => {
    const map = { 'K': '♔', 'Q': '♕', 'R': '♖', 'B': '♗', 'N': '♘', 'P': '♙', 'k': '♔', 'q': '♕', 'r': '♖', 'b': '♗', 'n' : '♘', 'p': '♙' };
    return map[p] || '';
};

watch(() => game.status, (s) => { if (s !== 'active') playSound('gameOver'); });
watch(() => game.player_ability_bar, (v) => { if (v === 100) playSound('ability'); });
watch(() => game.move_history.length, () => {
    nextTick(() => {
        if (moveHistoryRef.value) {
            moveHistoryRef.value.scrollTop = moveHistoryRef.value.scrollHeight;
        }
    });
});

onMounted(async () => {
    try {
        const response = await axios.get('/api/abilities');
        availableAbilities.value = response.data;
    } catch (error) {
        console.error("Failed to load abilities:", error);
    }
    const urlParams = new URLSearchParams(window.location.search);
    const gameId = urlParams.get('game');
    if (gameId) {
        try {
            const response = await axios.get(`/api/games/${gameId}`);
            const gameData = response.data.game;
            Object.assign(game, gameData);
            game.move_history = response.data.moveHistory ? response.data.moveHistory.map(m => m.notation) : [];
            selectedAbility.value = game.player_ability;
            view.value = 'game';
            setupEcho();
        } catch (error) {
            console.error("Failed to load game:", error);
            view.value = 'selection'; // Fallback to selection
        }
    }
});
</script>

<style scoped>
.shake-animation { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; }
@keyframes shake { 10%, 90% { transform: translate3d(-1px, 0, 0); } 20%, 80% { transform: translate3d(2px, 0, 0); } 30%, 50%, 70% { transform: translate3d(-4px, 0, 0); } 40%, 60% { transform: translate3d(4px, 0, 0); } }
.glow-blue { box-shadow: 0 0 15px #3b82f6, 0 0 30px #3b82f6; }
.glow-red { box-shadow: 0 0 15px #ef4444, 0 0 30px #ef4444; }
.glow-ability { 
    filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.8)) drop-shadow(0 0 12px rgba(255, 215, 0, 0.4));
    animation: pulse-glow 2s infinite ease-in-out;
}
@keyframes pulse-glow {
    0%, 100% { filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.8)) drop-shadow(0 0 12px rgba(255, 215, 0, 0.4)); }
    50% { filter: drop-shadow(0 0 15px rgba(255, 215, 0, 1)) drop-shadow(0 0 20px rgba(255, 215, 0, 0.6)); }
}
.glow-enpassant {
    filter: drop-shadow(0 0 8px rgba(0, 191, 255, 0.8)) drop-shadow(0 0 12px rgba(0, 191, 255, 0.4));
    animation: pulse-glow-blue 2s infinite ease-in-out;
}
@keyframes pulse-glow-blue {
    0%, 100% { filter: drop-shadow(0 0 8px rgba(0, 191, 255, 0.8)) drop-shadow(0 0 12px rgba(0, 191, 255, 0.4)); }
    50% { filter: drop-shadow(0 0 15px rgba(0, 191, 255, 1)) drop-shadow(0 0 20px rgba(0, 191, 255, 0.6)); }
}
.animate-fade-in { animation: fadeIn 0.8s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-bounce-short { animation: bounceShort 0.3s ease-in-out; }
@keyframes bounceShort { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
</style>
