import './bootstrap';
import { createApp } from 'vue';
import ChatInterface from './components/ChatInterface.vue';
import DocumentUpload from './components/DocumentUpload.vue';

// Crear aplicación Vue
const app = createApp({});

// Registrar componentes
app.component('chat-interface', ChatInterface);
app.component('document-upload', DocumentUpload);

// Montar aplicación
app.mount('#app');
