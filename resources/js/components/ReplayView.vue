<template>
  <div class="flex flex-col items-center gap-6 p-8 bg-gray-50 min-h-screen">
    <div class="flex justify-between w-full max-w-[800px] items-center">
        <h1 class="text-3xl font-extrabold text-gray-800">Match Replay</h1>
        <div class="flex gap-4 items-center">
            <a :href="`/api/games/${gameId}/export-pgn`" target="_blank" class="text-xs font-bold text-green-600 border border-green-200 px-3 py-1 rounded hover:bg-green-50">Export PGN</a>
            <button @click="$emit('close')" class="text-sm font-bold text-blue-600 hover:underline">Back to Game</button>
        </div>
    </div>

    <div class="flex flex-row gap-8 w-full max-w-[1000px] justify-center items-start">
        <!-- Chess Board -->
        <div class="space-y-4">
            <div class="grid grid-cols-8 grid-rows-8 w-[400px] h-[400px] border-4 border-gray-800 shadow-xl rounded-sm overflow-hidden bg-white">
                <template v-for="(row, rIndex) in currentBoardGrid" :key="'row-' + rIndex">
                <div 
                    v-for="(piece, cIndex) in row" 
                    :key="rIndex + '-' + cIndex"
                    class="relative flex items-center justify-center text-3xl"
                    :class="[
                        (rIndex + cIndex) % 2 === 0 ? 'bg-[#eeeed2]' : 'bg-[#769656]'
                    ]"
                >
                    <span v-if="piece" class="drop-shadow-sm" :class="isWhitePiece(piece) ? 'text-white' : 'text-gray-900'">
                    {{ getPieceUnicode(piece) }}
                    </span>
                </div>
                </template>
            </div>

            <!-- Controls -->
            <div class="flex justify-center gap-4">
                <button 
                    @click="currentMoveIndex = -1" 
                    class="p-2 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50"
                    :disabled="currentMoveIndex === -1"
                >⏮</button>
                <button 
                    @click="currentMoveIndex--" 
                    class="p-2 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50"
                    :disabled="currentMoveIndex === -1"
                >◀</button>
                <div class="flex items-center px-4 font-mono text-sm bg-white border rounded">
                    Move: {{ currentMoveIndex + 1 }} / {{ moves.length }}
                </div>
                <button 
                    @click="currentMoveIndex++" 
                    class="p-2 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50"
                    :disabled="currentMoveIndex === moves.length - 1"
                >▶</button>
                <button 
                    @click="currentMoveIndex = moves.length - 1" 
                    class="p-2 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50"
                    :disabled="currentMoveIndex === moves.length - 1"
                >⏭</button>
            </div>
        </div>

        <!-- Move History & Analysis -->
        <div class="flex-1 max-w-[400px] bg-white border rounded-lg shadow-sm overflow-hidden flex flex-col h-[450px]">
            <div class="p-4 bg-gray-800 text-white font-bold text-sm uppercase tracking-widest">Move History</div>
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <div 
                    v-for="(m, index) in moves" 
                    :key="m.id"
                    @click="currentMoveIndex = index"
                    class="p-3 border rounded-md cursor-pointer transition-colors flex justify-between items-center"
                    :class="currentMoveIndex === index ? 'bg-blue-50 border-blue-300 ring-1 ring-blue-300' : 'hover:bg-gray-50'"
                >
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-gray-400">#{{ m.turn_number }}</span>
                        <span class="font-bold text-gray-800">{{ m.from_square }} ➔ {{ m.to_square }}</span>
                    </div>
                    
                    <!-- Analysis Badge -->
                    <div v-if="m.analysis" class="flex flex-col items-end">
                        <span v-if="parsedAnalysis(m.analysis).is_blunder" class="px-2 py-0.5 bg-red-100 text-red-600 text-[10px] font-black rounded uppercase">Blunder!</span>
                        <span class="text-[9px] text-gray-400 mt-1 italic">
                            Better: {{ parsedAnalysis(m.analysis).suggested_move?.from }}{{ parsedAnalysis(m.analysis).suggested_move?.to }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    gameId: { type: Number, required: true }
});

const moves = ref([]);
const currentMoveIndex = ref(-1);
const initialFen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

const currentFen = computed(() => {
    if (currentMoveIndex.value === -1) return initialFen;
    return moves.value[currentMoveIndex.value].fen_after;
});

const currentBoardGrid = computed(() => {
    const placement = currentFen.value.split(' ')[0];
    const rows = placement.split('/');
    return rows.map(row => {
        const gridRow = [];
        for (let char of row) {
            if (!isNaN(char)) {
                for (let i = 0; i < parseInt(char); i++) gridRow.push(null);
            } else {
                gridRow.push(char);
            }
        }
        return gridRow;
    });
});

const fetchReplay = async () => {
    const response = await axios.get(`/api/games/${props.gameId}/replay`);
    moves.value = response.data.moves;
    currentMoveIndex.value = moves.value.length - 1;
};

const parsedAnalysis = (json) => {
    try { return JSON.parse(json); } catch(e) { return {}; }
};

const isWhitePiece = (p) => p && p === p.toUpperCase();
const getPieceUnicode = (p) => {
    const map = { 'K': '♔', 'Q': '♕', 'R': '♖', 'B': '♗', 'N': '♘', 'P': '♙', 'k': '♔', 'q': '♕', 'r': '♖', 'b': '♗', 'n' : '♘', 'p': '♙' };
    return map[p] || '';
};

onMounted(fetchReplay);
</script>
