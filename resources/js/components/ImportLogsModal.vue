<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
        @click.self="close"
        @keydown.esc="close"
    >
        <div class="bg-white border border-gray-200 rounded-2xl w-full max-w-3xl max-h-[85vh] flex flex-col shadow-2xl overflow-hidden">
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-200 flex items-start justify-between bg-gray-50/80">
                <div>
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-bold text-gray-900">Import error logs</h3>
                        <span class="font-mono text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600 border border-gray-200">
                            #{{ importData?.id }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                        <span>File: <strong class="text-gray-800">{{ importData?.file_name }}</strong></span>
                        <span>•</span>
                        <span class="text-rose-600 font-semibold">{{ logs.length }} incorrect records</span>
                    </p>
                </div>

                <button
                    @click="close"
                    class="p-2 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition cursor-pointer"
                    title="Close (Esc)"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <!-- Tabs -->
            <div v-if="showTransactions" class="px-6 border-b border-gray-200 flex gap-1 bg-white">
                <button
                    v-if="logs.length > 0"
                    @click="activeTab = 'logs'"
                    :class="activeTab === 'logs'
                        ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold'
                        : 'text-gray-500 hover:text-gray-700'"
                    class="px-3 py-3 text-xs transition"
                >
                    Error logs ({{ logs.length }})
                </button>
                <button
                    v-if="transactions.length > 0"
                    @click="activeTab = 'transactions'"
                    :class="activeTab === 'transactions'
                        ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold'
                        : 'text-gray-500 hover:text-gray-700'"
                    class="px-3 py-3 text-xs transition"
                >
                    Valid transactions ({{ transactions.length }})
                </button>
            </div>

            <!-- Logs Content -->
            <div v-if="activeTab === 'logs'" class="flex-1 overflow-y-auto p-6 space-y-3">
                <!-- Loading state -->
                <div v-if="isLoading" class="py-12 text-center text-gray-500">
                    <svg class="w-7 h-7 animate-spin mx-auto text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <p class="text-xs">Loading logs...</p>
                </div>

                <!-- No errors -->
                <div v-else-if="logs.length === 0" class="py-12 text-center text-gray-500">
                    <div class="w-12 h-12 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center mx-auto mb-3 text-emerald-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">No errors in this import</p>
                    <p class="text-xs text-gray-500 mt-1">All records from the file have been successfully validated and saved to the database.</p>
                </div>

                <!-- Empty filter search -->
                <div v-else-if="filteredLogs.length === 0" class="py-8 text-center text-gray-400 text-xs">
                    Brak wyników dla podanej frazy "{{ searchQuery }}".
                </div>

                <!-- Logs Table/List -->
                <div v-else class="space-y-2.5">
                    <div
                        v-for="(log, index) in filteredLogs"
                        :key="log.id || index"
                        class="p-4 bg-gray-50 border border-gray-200 rounded-xl hover:border-gray-300 transition"
                    >
                        <div class="flex items-start justify-between gap-4 mb-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded bg-rose-50 border border-rose-200 text-rose-700 text-xs font-mono font-medium">
                                    {{ log.transaction_id || '(brak ID transakcji)' }}
                                </span>
                            </div>
                            <span class="text-[11px] text-gray-400 font-mono">
                                {{ formatDate(log.created_at) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-700 gap-2 flex items-center">
                            <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="text-gray-800 leading-relaxed whitespace-pre-line">{{ log.error_message }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div v-else-if="activeTab === 'transactions'" class="flex-1 overflow-y-auto p-6 space-y-2.5">
                <div
                    v-for="tx in transactions"
                    :key="tx.id"
                    class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-xs"
                >
                    <div class="flex items-center justify-between gap-4">
                        <span class="font-mono text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">{{ tx.transaction_id }}</span>
                        <span class="text-gray-400 font-mono">{{ tx.transaction_date }}</span>
                    </div>
                    <div class="flex items-center gap-4 mt-2 text-gray-600">
                        <span>IBAN: <strong class="text-gray-900">{{ tx.account_number }}</strong></span>
                        <span>Kwota: <strong class="text-gray-900">{{ tx.amount }} {{ tx.currency }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end">
                <button
                    @click="close"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 transition cursor-pointer shadow-2xs"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    importData: {
        type: Object,
        default: null,
    },
    logs: {
        type: Array,
        default: () => [],
    },
    transactions: {
        type: Array,
        default: () => [],
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const showTransactions = window.AppConfig?.showTransactions ?? false;

const searchQuery = ref('');
const activeTab = ref('logs');

watch(
    () => [props.isOpen, props.logs.length, props.transactions.length],
    () => {
        if (props.logs.length === 0 && props.transactions.length > 0) {
            activeTab.value = 'transactions';
        } else {
            activeTab.value = 'logs';
        }
    }
);

const filteredLogs = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.logs;
    }
    const q = searchQuery.value.toLowerCase();
    return props.logs.filter(log =>
        (log.transaction_id && log.transaction_id.toLowerCase().includes(q)) ||
        (log.error_message && log.error_message.toLowerCase().includes(q))
    );
});

function close() {
    searchQuery.value = '';
    activeTab.value = 'logs';
    emit('close');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleTimeString('pl-PL');
}
</script>
