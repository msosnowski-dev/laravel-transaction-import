<template>
    <div class="min-h-screen bg-gray-50 text-gray-900 flex flex-col">
        <!-- Top Navigation -->
        <header class="border-b border-gray-200 bg-white sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center">
                <div>
                    <h1 class="text-base font-bold text-gray-900 tracking-tight">
                        Import transaction files
                    </h1>
                    <p class="text-xs text-gray-500 hidden sm:block">Bank transaction import system</p>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-8">
            <!-- Toast notification -->
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="toast.show"
                    :class="[
                        'p-4 rounded-xl shadow-md border flex items-center justify-between gap-3 text-sm font-medium',
                        toast.type === 'success'
                            ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                            : 'bg-rose-50 border-rose-200 text-rose-800'
                    ]"
                >
                    <div class="flex items-center gap-2.5">
                        <svg v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ toast.message }}</span>
                    </div>
                    <button @click="toast.show = false" class="text-gray-400 hover:text-gray-600 text-xs font-semibold">✕</button>
                </div>
            </transition>

            <!-- Two column layout: Upload on left, Imports table on right -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Right Column: Imports List -->
                <div class="lg:col-span-9">
                    <ImportsList
                        :imports="importsList"
                        :meta="paginationMeta"
                        :is-loading="isLoadingList"
                        @refresh="loadImports(paginationMeta?.current_page || 1)"
                        @change-page="loadImports"
                        @view-logs="openLogsModal"
                    />
                </div>
                <!-- Left Column: Upload Form -->
                <div class="lg:col-span-3">
                    <ImportUploadForm @import-success="handleImportSuccess" />
                </div>
            </div>
        </main>

        <!-- Error Logs Modal -->
        <ImportLogsModal
            :is-open="isLogsModalOpen"
            :import-data="activeImport"
            :logs="activeImportLogs"
            :is-loading="isLoadingLogs"
            @close="closeLogsModal"
        />

        <!-- Footer -->
        <footer class="border-t border-gray-200 py-6 text-center text-xs text-gray-400">
        </footer>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { fetchImports, fetchImportDetails } from './api/imports';
import ImportUploadForm from './components/ImportUploadForm.vue';
import ImportsList from './components/ImportsList.vue';
import ImportLogsModal from './components/ImportLogsModal.vue';

const importsList = ref([]);
const paginationMeta = ref(null);
const isLoadingList = ref(false);

const isLogsModalOpen = ref(false);
const activeImport = ref(null);
const activeImportLogs = ref([]);
const isLoadingLogs = ref(false);

const toast = ref({
    show: false,
    message: '',
    type: 'success',
});

function showToast(message, type = 'success') {
    toast.value = {
        show: true,
        message,
        type,
    };
    setTimeout(() => {
        toast.value.show = false;
    }, 5000);
}

// KPI metrics computed
const totalImportsCount = computed(() => paginationMeta.value?.total ?? importsList.value.length);
const totalSuccessfulRecords = computed(() => {
    return importsList.value.reduce((sum, item) => sum + (item.successful_records || 0), 0);
});
const totalFailedRecords = computed(() => {
    return importsList.value.reduce((sum, item) => sum + (item.failed_records || 0), 0);
});
const successRate = computed(() => {
    const total = totalSuccessfulRecords.value + totalFailedRecords.value;
    if (total === 0) return 100;
    return Math.round((totalSuccessfulRecords.value / total) * 100);
});

async function loadImports(page = 1) {
    isLoadingList.value = true;
    try {
        const response = await fetchImports(page);
        importsList.value = response.data || [];
        paginationMeta.value = response.meta || null;
    } catch (err) {
        showToast(err.message || 'Failed to load import history.', 'error');
    } finally {
        isLoadingList.value = false;
    }
}

async function handleImportSuccess(importData) {
    if (importData.status === 'failed') {
        showToast(`File import error ${importData.file_name} – all records were rejected.`, 'error');
    } else if (importData.status === 'partial') {
        showToast(`File ${importData.file_name} partially imported (incorrect records: ${importData.failed_records}).`, 'warning');
    } else {
        showToast(`File successfully imported: ${importData.file_name} (${importData.successful_records} records).`, 'success');
    }
    await loadImports(1);
}

async function openLogsModal(importItem) {
    activeImport.value = importItem;
    isLogsModalOpen.value = true;
    isLoadingLogs.value = true;

    try {
        // If logs were already included in the payload
        if (importItem.logs && Array.isArray(importItem.logs)) {
            activeImportLogs.value = importItem.logs;
        } else {
            const details = await fetchImportDetails(importItem.id);
            activeImportLogs.value = details.data?.logs || [];
        }
    } catch (err) {
        showToast(err.message || 'Błąd podczas pobierania logów.', 'error');
        activeImportLogs.value = [];
    } finally {
        isLoadingLogs.value = false;
    }
}

function closeLogsModal() {
    isLogsModalOpen.value = false;
    activeImport.value = null;
    activeImportLogs.value = [];
}

onMounted(() => {
    loadImports();
});
</script>
