<script setup>
import { computed } from 'vue';
import AppIcon from '@/components/common/AppIcon.vue';

const props = defineProps({
    statistics: { type: Object, required: true },
});

const completionRate = computed(() =>
    props.statistics.total === 0 ? 0 : Math.round((props.statistics.completed / props.statistics.total) * 100),
);

const cards = computed(() => [
    { label: 'Total tasks', value: props.statistics.total, icon: 'squares', tone: 'bg-indigo-50 text-indigo-600' },
    { label: 'Completed', value: props.statistics.completed, icon: 'check-circle', tone: 'bg-emerald-50 text-emerald-600' },
    { label: 'Pending', value: props.statistics.pending, icon: 'clock', tone: 'bg-amber-50 text-amber-600' },
]);
</script>

<template>
    <section aria-label="Task statistics" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="card in cards" :key="card.label" class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl" :class="card.tone">
                <AppIcon :name="card.icon" class="size-6" />
            </span>
            <div>
                <p class="text-sm text-slate-500">{{ card.label }}</p>
                <p class="text-2xl font-semibold tabular-nums text-slate-900">{{ card.value }}</p>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-900/5">
            <div class="flex items-baseline justify-between">
                <p class="text-sm text-slate-500">Completion rate</p>
                <p class="text-2xl font-semibold tabular-nums text-slate-900">{{ completionRate }}%</p>
            </div>
            <div
                class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"
                role="progressbar"
                :aria-valuenow="completionRate"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="Completion rate"
            >
                <div class="h-full rounded-full bg-emerald-500 transition-[width] duration-500" :style="{ width: `${completionRate}%` }" />
            </div>
        </div>
    </section>
</template>
