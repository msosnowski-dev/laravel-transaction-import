<template>
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Import history
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">List of processed files and validation logs</p>
            </div>

            <button
                @click="$emit('refresh')"
                :disabled="isLoading"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 transition disabled:opacity-50 cursor-pointer shadow-xs"
                title="Refresh list"
            >
                <svg :class="['w-4 h-4 text-gray-500', isLoading ? 'animate-spin' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Refresh</span>
            </button>
        </div>

        <!-- Empty state -->
        <div v-if="!isLoading && imports.length === 0" class="text-center py-12">
            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3 text-gray-400">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-700">No registered imports</p>
            <p class="text-xs text-gray-400 mt-1">Upload the first bank statement file using the form on the right.</p>
        </div>

        <!-- Table of imports -->
        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-500 text-xs uppercase tracking-wider bg-gray-50/50">
                        <th class="py-3 px-3">File</th>
                        <th class="py-3 px-3">Records</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Created</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr
                        v-for="item in imports"
                        :key="item.id"
                        class="hover:bg-gray-50/80 transition group"
                    >
                        <td class="py-3.5 px-3">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-gray-400">#{{ item.id }}</span>
                                <span class="font-medium text-gray-900 truncate max-w-[200px]" :title="item.file_name">
                                    {{ item.file_name }}
                                </span>
                            </div>
                        </td>

                        <td class="py-3.5 px-3 whitespace-nowrap">
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-gray-900 font-semibold">{{ item.total_records }}</span>
                                <span class="text-gray-400">total:</span>
                                <span class="text-emerald-600 font-medium" title="Correct records">✓ {{ item.successful_records }}</span>
                                <span v-if="item.failed_records > 0" class="text-rose-600 font-medium" title="Incorrect records">✕ {{ item.failed_records }}</span>
                            </div>
                        </td>

                        <td class="py-3.5 px-3 whitespace-nowrap">
                            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border', getStatusBadgeClasses(item.status)]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClasses(item.status)"></span>
                                {{ item.status }}
                            </span>
                        </td>

                        <td class="py-3.5 px-3 text-xs text-gray-500 whitespace-nowrap">
                            {{ formatDate(item.created_at) }}
                        </td>

                        <td class="py-3.5 px-3 text-right whitespace-nowrap">
                            <button
                                v-if="item.failed_records > 0 || item.status === 'failed' || showTransactions"
                                @click="$emit('view-logs', item)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer"
                                :class="[
                                    item.failed_records > 0 || item.status === 'failed'
                                        ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'
                                        : 'bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 shadow-2xs'
                                ]"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>{{ showTransactions && item.failed_records == 0 && item.status !== 'failed' ? 'View details' : 'Error logs' }}</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between pt-4 mt-2 border-t border-gray-200 text-xs text-gray-500">
            <div>
                Page <span class="font-semibold text-gray-900">{{ meta.current_page }}</span> of <span class="font-semibold text-gray-900">{{ meta.last_page }}</span>
                (total {{ meta.total }} imports)
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="$emit('change-page', meta.current_page - 1)"
                    :disabled="meta.current_page <= 1"
                    class="px-2.5 py-1 rounded bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-gray-700 border border-gray-300 transition shadow-2xs"
                >
                    Previous
                </button>
                <button
                    @click="$emit('change-page', meta.current_page + 1)"
                    :disabled="meta.current_page >= meta.last_page"
                    class="px-2.5 py-1 rounded bg-white hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed text-gray-700 border border-gray-300 transition shadow-2xs"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>

const showTransactions = window.AppConfig?.showTransactions ?? false;

defineProps({
    imports: {
        type: Array,
        required: true,
    },
    meta: {
        type: Object,
        default: null,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['refresh', 'view-logs', 'change-page']);

function getStatusBadgeClasses(status) {
    switch (status) {
        case 'success':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'partial':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'failed':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        case 'processing':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        default:
            return 'bg-gray-100 text-gray-700 border-gray-200';
    }
}

function getStatusDotClasses(status) {
    switch (status) {
        case 'success': return 'bg-emerald-500';
        case 'partial': return 'bg-amber-500';
        case 'failed': return 'bg-rose-500';
        case 'processing': return 'bg-blue-500 animate-ping';
        default: return 'bg-gray-400';
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleString('pl-PL');
}
</script>
