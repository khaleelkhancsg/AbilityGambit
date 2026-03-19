import './bootstrap';
import { createApp } from 'vue';
import ChessBoard from './components/ChessBoard.vue';
import ReplayView from './components/ReplayView.vue';

console.log('Vue App Initializing...');

const app = createApp({});

app.component('chess-board', ChessBoard);
app.component('replay-view', ReplayView);

app.mount('#app');

console.log('Vue App Mounted');
