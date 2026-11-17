<script setup>
import AppIcon from '@/components/common/AppIcon.vue';
import BaseInput from '@/components/common/BaseInput.vue';

const search = defineModel('search', { type: String, default: '' });
const status = defineModel('status', { type: String, default: 'all' });

defineProps({
    counts: { type: Object, default: () => ({}) },
});

const STATUSES = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'completed', label: 'Completed' },
];
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <BaseInput
            v-model="search"
            class="w-full sm:max-w-xs"
            label="Search tasks"
            hide-label
            type="search"
            name="search"
            placeholder="Search by title…"
            autocomplete="off"
        >
            <template #leading>
                <AppIcon name="search" class="size-4" />
            </template>
            <template v-if="search" #trailing>
                <button
                    type="button"
                    class="rounded p-1 text-slate-400 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-indigo-600"
                    aria-label="Clear search"
                    @click="search = ''"
                >
                    <AppIcon name="x-mark" class="size-4" />
                </button>
            </template>
        </BaseInput>

        <!-- native radios: arrow-key navigation and screen reader semantics for free -->
        <fieldset class="flex rounded-lg bg-slate-100 p-1">
            <legend class="sr-only">Filter by status</legend>
            <label v-for="option in STATUSES" :key="option.value" class="flex-1 sm:flex-none">
                <input v-model="status" type="radio" name="status" :value="option.value" class="peer sr-only" />
                <span
                    class="flex cursor-pointer items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm peer-focus-visible:outline-2 peer-focus-visible:outline-indigo-600 hover:text-slate-900"
                >
                    {{ option.label }}
                    <span v-if="counts[option.value] !== undefined" class="text-xs tabular-nums text-slate-400">
                        {{ counts[option.value] }}
                    </span>
                </span>
            </label>
        </fieldset>
    </div>
</template>
