<template>
    <div class="flex flex-col h-screen max-w-6xl mx-auto">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        AI Assistant
                    </h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Powered by Ollama + MCP
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Status indicators -->
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <div :class="[
                                'w-2 h-2 rounded-full',
                                isConnected ? 'bg-green-500' : 'bg-red-500'
                            ]"></div>
                            <span class="text-xs text-gray-600 dark:text-gray-400">
                                {{ isConnected ? 'Connected' : 'Disconnected' }}
                            </span>
                        </div>
                    </div>

                    <!-- Tools button -->
                    <button
                        @click="showToolsPanel = !showToolsPanel"
                        class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        {{ showToolsPanel ? 'Hide Tools' : 'Show Tools' }}
                    </button>
                </div>
            </div>
        </header>

        <div class="flex-1 flex overflow-hidden">
            <!-- Chat Area -->
            <main class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900">
                <!-- Messages Container -->
                <div
                    ref="messagesContainer"
                    class="flex-1 overflow-y-auto px-6 py-4 space-y-4"
                >
                    <!-- Welcome message -->
                    <div v-if="messages.length === 0" class="text-center py-12">
                        <div class="inline-block p-4 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                            <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            Welcome to AI Assistant
                        </h2>
                        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                            Ask me anything or try mathematical operations like "add 5 and 3" or "divide 100 by 4"
                        </p>
                    </div>

                    <!-- Messages -->
                    <div
                        v-for="(message, index) in messages"
                        :key="index"
                        :class="[
                            'flex',
                            message.type === 'user' ? 'justify-end' : 'justify-start'
                        ]"
                    >
                        <div
                            :class="[
                                'max-w-3xl px-4 py-3 rounded-lg',
                                message.type === 'user'
                                    ? 'bg-blue-600 text-white'
                                    : message.type === 'error'
                                    ? 'bg-red-100 dark:bg-red-900 text-red-900 dark:text-red-100'
                                    : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                            ]"
                        >
                            <div class="whitespace-pre-wrap break-words">{{ message.content }}</div>

                            <!-- Metadata -->
                            <div
                                v-if="message.metadata && message.metadata.tool_used"
                                class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 text-xs"
                            >
                                <span class="opacity-75">
                                    Tool: {{ message.metadata.tool_used }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div v-if="isLoading" class="flex justify-start">
                        <div class="bg-white dark:bg-gray-800 px-4 py-3 rounded-lg shadow-sm">
                            <div class="flex items-center gap-2">
                                <div class="flex gap-1">
                                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                </div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Thinking...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <form @submit.prevent="sendMessage" class="flex gap-2">
                        <input
                            v-model="userInput"
                            type="text"
                            placeholder="Type your message..."
                            :disabled="isLoading"
                            class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 disabled:opacity-50"
                        />
                        <button
                            type="submit"
                            :disabled="isLoading || !userInput.trim()"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                        >
                            <span v-if="!isLoading">Send</span>
                            <span v-else>Sending...</span>
                        </button>
                    </form>
                </div>
            </main>

            <!-- Tools Panel (Sidebar) -->
            <aside
                v-if="showToolsPanel"
                class="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 overflow-y-auto"
            >
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Available Tools
                    </h3>

                    <div v-if="loadingTools" class="text-center py-4">
                        <div class="inline-block w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    </div>

                    <div v-else-if="tools.length > 0" class="space-y-3">
                        <div
                            v-for="tool in tools"
                            :key="tool.name"
                            class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                        >
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm">
                                {{ tool.name }}
                            </h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                {{ tool.description }}
                            </p>
                        </div>
                    </div>

                    <div v-else class="text-center py-4 text-gray-600 dark:text-gray-400 text-sm">
                        No tools available
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ChatInterface',

    data() {
        return {
            messages: [],
            userInput: '',
            isLoading: false,
            isConnected: true,
            showToolsPanel: false,
            tools: [],
            loadingTools: false,
        };
    },

    mounted() {
        this.loadTools();
    },


    methods: {
        async sendMessage() {
            if (!this.userInput.trim() || this.isLoading) return;

            const message = this.userInput.trim();
            this.userInput = '';

            // Add user message
            this.messages.push({
                type: 'user',
                content: message,
            });

            this.scrollToBottom();
            this.isLoading = true;

            try {
                await this.sendMessageWithStreaming(message);
            } catch (error) {
                console.error('Error sending message:', error);
                this.messages.push({
                    type: 'error',
                    content: `Error: ${error.message}`,
                });
            } finally {
                this.isLoading = false;
                this.scrollToBottom();
            }
        },

        async sendMessageWithStreaming(message) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch('/api/chat/message/stream', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'text/event-stream',
                    },
                    body: JSON.stringify({ message }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                let currentMessage = {
                    type: 'assistant',
                    content: '',
                    metadata: null,
                };
                let messageIndex = null;

                while (true) {
                    const { done, value } = await reader.read();

                    if (done) {
                        break;
                    }

                    const chunk = decoder.decode(value, { stream: true });
                    const lines = chunk.split('\n');

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            try {
                                const data = JSON.parse(line.substring(6));

                                if (data.type === 'start') {
                                    continue;
                                }

                                if (data.type === 'end') {
                                    return;
                                }

                                if (data.error) {
                                    this.messages.push({
                                        type: 'error',
                                        content: data.error,
                                    });
                                    throw new Error(data.error);
                                }

                                // Handle streaming content
                                if (data.content !== undefined) {
                                    if (messageIndex === null) {
                                        this.messages.push(currentMessage);
                                        messageIndex = this.messages.length - 1;
                                    }

                                    // Update message content
                                    if (data.full_content !== undefined) {
                                        this.messages[messageIndex].content = data.full_content;
                                    } else {
                                        this.messages[messageIndex].content += data.content;
                                    }

                                    if (data.metadata) {
                                        this.messages[messageIndex].metadata = data.metadata;
                                    }

                                    this.scrollToBottom();
                                }
                            } catch (parseError) {
                                console.error('Error parsing SSE data:', parseError);
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Streaming error:', error);
                this.isConnected = false;

                setTimeout(() => {
                    this.isConnected = true;
                }, 3000);

                throw error;
            }
        },


        async loadTools() {
            this.loadingTools = true;
            try {
                const response = await fetch('/api/chat/tools');
                const data = await response.json();
                this.tools = data.tools || [];
            } catch (error) {
                console.error('Error loading tools:', error);
            } finally {
                this.loadingTools = false;
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },
    },
};
</script>

<style scoped>
@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-0.5rem);
    }
}

.animate-bounce {
    animation: bounce 1s infinite;
}
</style>
