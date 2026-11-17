<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    meta: { type: Object, required: true },
    disabled: Boolean,
});

const emit = defineEmits(['change']);

const current = computed(() => props.meta.current_page);
const last = computed(() => props.meta.last_page);

// first, last, and a window around the current page, with gaps marked as null
const pages = computed(() => {
    const wanted = new Set([1, last.value, current.value - 1, current.value, current.value + 1]);
    const sorted = [...wanted].filter((page) => page >= 1 && page <= last.value).sort((a, b) => a - b);

    return sorted.flatMap((page, index) => (index > 0 && page - sorted[index - 1] > 1 ? [null, page] : [page]));
});

function go(page) {
    if (!props.disabled && page >= 1 && page <= last.value && page !== current.value) {
        emit('change', page);
    }
}

const navButton =
    'inline-flex size-9 items-center justify-center rounded-lg text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:opacity-40';
</script>

<template>
    <nav class="flex flex-col items-center justify-between gap-3 sm:flex-row" aria-label="Pagination">
        <p class="text-sm text-slate-500">
            Showing <span class="font-medium text-slate-700">{{ meta.from ?? 0 }}</span>–<span class="font-medium text-slate-700">{{ meta.to ?? 0 }}</span>
            of <span class="font-medium text-slate-700">{{ meta.total }}</span>
        </p>

        <div v-if="last > 1" class="flex items-center gap-1">
            <button
                type="button"
                :class="[navButton, 'text-slate-500 hover:bg-slate-100']"
                :disabled="disabled || current === 1"
                aria-label="Previous page"
                @click="go(current - 1)"
            >
                <AppIcon name="chevron-left" class="size-4" />
            </button>

            <template v-for="(page, index) in pages" :key="page ?? `gap-${index}`">
                <span v-if="page === null" class="px-1 text-slate-400" aria-hidden="true">…</span>
                <button
                    v-else
                    type="button"
                    :class="[navButton, page === current ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100']"
                    :aria-current="page === current ? 'page' : undefined"
                    :aria-label="`Page ${page}`"
                    :disabled="disabled"
                    @click="go(page)"
                >
                    {{ page }}
                </button>
            </template>

            <button
                type="button"
                :class="[navButton, 'text-slate-500 hover:bg-slate-100']"
                :disabled="disabled || current === last"
                aria-label="Next page"
                @click="go(current + 1)"
            >
                <AppIcon name="chevron-right" class="size-4" />
            </button>
        </div>
    </nav>
</template>
