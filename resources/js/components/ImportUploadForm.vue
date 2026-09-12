<template>
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Send file
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Upload a bank statement in CSV, JSON, or XML format.</p>
            </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
            <!-- Dropzone -->
            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                @click="triggerFileInput"
                :class="[
                    'border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all duration-200',
                    isDragging
                        ? 'border-indigo-500 bg-indigo-50 scale-[0.99]'
                        : selectedFile
                            ? 'border-emerald-500 bg-emerald-50/50'
                            : 'border-gray-300 hover:border-gray-400 bg-gray-50 hover:bg-gray-100/60'
                ]"
            >
                <input
                    ref="fileInput"
                    type="file"
                    accept=".csv,.json,.xml,text/csv,application/json,text/xml,application/xml"
                    @change="handleFileChange"
                    class="hidden"
                />

                <div v-if="!selectedFile" class="flex flex-col items-center justify-center py-3">
                    <div class="w-12 h-12 mb-3 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-700">
                        Drag the file here or <span class="text-indigo-600 underline decoration-indigo-600/30 underline-offset-2">browse</span>
                    </p>
                </div>

                <!-- Selected file preview -->
                <div v-else class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg shadow-xs">
                    <div class="flex items-center gap-3 overflow-hidden text-left">
                        <div class="w-10 h-10 rounded-lg bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-700 font-bold text-xs uppercase">
                            {{ fileExtension }}
                        </div>
                        <div class="truncate">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ selectedFile.name }}</p>
                            <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click.stop="clearSelectedFile"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-gray-100 transition"
                        title="Usuń plik"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Error message alert -->
            <div v-if="errorMessage" class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs flex items-start gap-2.5">
                <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ errorMessage }}</span>
            </div>

            <!-- Submit button -->
            <button
                type="submit"
                :disabled="!selectedFile || isSubmitting"
                class="w-full py-3 px-4 rounded-xl font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2 shadow-sm"
                :class="[
                    !selectedFile || isSubmitting
                        ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                        : 'bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white cursor-pointer shadow-indigo-600/20'
                ]"
            >
                <svg v-if="isSubmitting" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>{{ isSubmitting ? 'Processing and validating import...' : 'Start import' }}</span>
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { uploadImportFile } from '../api/imports';

const emit = defineEmits(['import-success']);

const fileInput = ref(null);
const selectedFile = ref(null);
const isDragging = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref('');

const fileExtension = computed(() => {
    if (!selectedFile.value) return '';
    const parts = selectedFile.value.name.split('.');
    return parts.length > 1 ? parts.pop().toLowerCase() : '';
});

function triggerFileInput() {
    if (fileInput.value) {
        fileInput.value.click();
    }
}

function handleFileChange(event) {
    const file = event.target.files?.[0];
    if (file) {
        processFileSelection(file);
    }
}

function handleDrop(event) {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) {
        processFileSelection(file);
    }
}

function processFileSelection(file) {
    errorMessage.value = '';
    const ext = file.name.split('.').pop()?.toLowerCase();
    if (!['csv', 'json', 'xml'].includes(ext)) {
        errorMessage.value = 'Invalid file extension. Please select a .csv, .json, or .xml file.';
        selectedFile.value = null;
        return;
    }
    selectedFile.value = file;
}

function clearSelectedFile() {
    selectedFile.value = null;
    errorMessage.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

async function handleSubmit() {
    if (!selectedFile.value || isSubmitting.value) return;

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const response = await uploadImportFile(selectedFile.value);
        emit('import-success', response.data);
        clearSelectedFile();
    } catch (err) {
        errorMessage.value = err.message;
    } finally {
        isSubmitting.value = false;
    }
}
</script>
