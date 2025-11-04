<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Document Management
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Upload documents to enhance AI responses with your knowledge base
                </p>
            </div>

            <div class="flex items-center gap-2">
                <div :class="[
                    'w-2 h-2 rounded-full',
                    ragStatus === 'available' ? 'bg-green-500' : 'bg-red-500'
                ]"></div>
                <span class="text-xs text-gray-600 dark:text-gray-400">
                    RAG Server: {{ ragStatus === 'available' ? 'Online' : 'Offline' }}
                </span>
            </div>
        </div>

        <!-- Upload Area -->
        <div
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            :class="[
                'border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer',
                isDragging
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
            ]"
            @click="$refs.fileInput.click()"
        >
            <input
                ref="fileInput"
                type="file"
                accept=".txt,.md,.pdf,.doc,.docx"
                class="hidden"
                @change="handleFileSelect"
            />

            <div class="flex flex-col items-center gap-3">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        Drop your document here or click to browse
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Supports: TXT, MD, PDF, DOC, DOCX
                    </p>
                </div>
            </div>
        </div>

        <!-- Upload Form -->
        <div v-if="selectedFile" class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ selectedFile.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ formatFileSize(selectedFile.size) }}
                        </p>
                    </div>
                </div>
                <button
                    @click="clearSelection"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Document Title
                    </label>
                    <input
                        v-model="documentTitle"
                        type="text"
                        maxlength="40"
                        placeholder="Enter document title (max 40 chars)"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Metadata (Optional, JSON)
                    </label>
                    <textarea
                        v-model="documentMetadata"
                        rows="2"
                        placeholder='{"author": "John Doe", "category": "Research"}'
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono text-xs"
                    ></textarea>
                </div>

                <button
                    @click="uploadDocument"
                    :disabled="!documentTitle || isUploading"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                >
                    <span v-if="!isUploading">Upload Document</span>
                    <span v-else>Uploading...</span>
                </button>
            </div>

            <!-- Upload Progress -->
            <div v-if="uploadProgress > 0" class="mt-3">
                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                    <span>Upload Progress</span>
                    <span>{{ uploadProgress }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                    <div
                        class="bg-blue-600 h-2 rounded-full transition-all"
                        :style="{ width: uploadProgress + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <div v-if="uploadMessage" class="mt-3">
                <div
                    :class="[
                        'p-3 rounded-lg text-sm',
                        uploadSuccess
                            ? 'bg-green-100 dark:bg-green-900 text-green-900 dark:text-green-100'
                            : 'bg-red-100 dark:bg-red-900 text-red-900 dark:text-red-100'
                    ]"
                >
                    {{ uploadMessage }}
                </div>
            </div>
        </div>

        <!-- Uploaded Documents List -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Uploaded Documents
            </h3>

            <div v-if="loadingDocuments" class="text-center py-8">
                <div class="inline-block w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="documents.length > 0" class="space-y-2">
                <div
                    v-for="doc in documents"
                    :key="doc.id"
                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                >
                    <div class="flex items-center gap-3 flex-1">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ doc.title }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Version {{ doc.version }} • {{ formatDate(doc.created_at) }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="deleteDocument(doc.id)"
                            class="px-3 py-1 text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-sm">No documents uploaded yet</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'DocumentUpload',

    data() {
        return {
            selectedFile: null,
            documentTitle: '',
            documentMetadata: '',
            isDragging: false,
            isUploading: false,
            uploadProgress: 0,
            uploadMessage: '',
            uploadSuccess: false,
            documents: [],
            loadingDocuments: false,
            ragStatus: 'checking',
        };
    },

    mounted() {
        this.checkRagStatus();
        this.loadDocuments();
    },

    methods: {
        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;

            if (files.length > 0) {
                this.selectedFile = files[0];
                this.documentTitle = this.selectedFile.name.replace(/\.[^/.]+$/, '');
            }
        },

        handleFileSelect(event) {
            const files = event.target.files;

            if (files.length > 0) {
                this.selectedFile = files[0];
                this.documentTitle = this.selectedFile.name.replace(/\.[^/.]+$/, '');
            }
        },

        clearSelection() {
            this.selectedFile = null;
            this.documentTitle = '';
            this.documentMetadata = '';
            this.uploadMessage = '';
            this.uploadProgress = 0;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        async uploadDocument() {
            if (!this.selectedFile || !this.documentTitle) {
                return;
            }

            this.isUploading = true;
            this.uploadMessage = '';
            this.uploadProgress = 0;

            try {
                const content = await this.readFileContent(this.selectedFile);

                let metadata = {};
                if (this.documentMetadata.trim()) {
                    try {
                        metadata = JSON.parse(this.documentMetadata);
                    } catch (e) {
                        throw new Error('Invalid JSON in metadata field');
                    }
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                this.uploadProgress = 30;

                const response = await fetch('/api/documents/upload', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        title: this.documentTitle,
                        content: content,
                        metadata: metadata,
                    }),
                });

                this.uploadProgress = 80;

                const result = await response.json();

                this.uploadProgress = 100;

                if (result.success) {
                    this.uploadSuccess = true;
                    this.uploadMessage = result.message || 'Document uploaded successfully!';
                    this.clearSelection();
                    await this.loadDocuments();
                } else {
                    this.uploadSuccess = false;
                    this.uploadMessage = result.message || 'Failed to upload document';
                }
            } catch (error) {
                console.error('Upload error:', error);
                this.uploadSuccess = false;
                this.uploadMessage = `Error: ${error.message}`;
            } finally {
                this.isUploading = false;
                setTimeout(() => {
                    this.uploadProgress = 0;
                }, 2000);
            }
        },

        async readFileContent(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();

                reader.onload = (e) => {
                    resolve(e.target.result);
                };

                reader.onerror = (e) => {
                    reject(new Error('Failed to read file'));
                };

                reader.readAsText(file);
            });
        },

        async loadDocuments() {
            this.loadingDocuments = true;
            try {
                // TODO: Implement endpoint to list all documents
                // For now, using empty array
                this.documents = [];
            } catch (error) {
                console.error('Error loading documents:', error);
            } finally {
                this.loadingDocuments = false;
            }
        },

        async deleteDocument(documentId) {
            if (!confirm('Are you sure you want to delete this document?')) {
                return;
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                const response = await fetch(`/api/documents/${documentId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const result = await response.json();

                if (result.success) {
                    await this.loadDocuments();
                } else {
                    alert(`Error: ${result.message}`);
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert(`Error deleting document: ${error.message}`);
            }
        },

        async checkRagStatus() {
            try {
                const response = await fetch('/api/documents/status');
                const result = await response.json();
                this.ragStatus = result.status || 'unavailable';
            } catch (error) {
                console.error('RAG status check error:', error);
                this.ragStatus = 'unavailable';
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        formatDate(dateString) {
            if (!dateString) return 'Unknown';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        },
    },
};
</script>
