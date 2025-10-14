import './bootstrap';
import { createApp } from 'vue';
import ChatInterface from './components/ChatInterface.vue';

// Crear aplicación Vue
const app = createApp({});

// Registrar componentes
app.component('chat-interface', ChatInterface);

// Montar aplicación
app.mount('#app');
