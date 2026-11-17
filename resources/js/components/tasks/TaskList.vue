<script setup>
import AppIcon from '@/components/common/AppIcon.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import LoadingSpinner from '@/components/common/LoadingSpinner.vue';
import TaskItem from './TaskItem.vue';

defineProps({
    tasks: { type: Array, required: true },
    loading: Boolean,
    loaded: Boolean,
    error: { type: String, default: null },
    busyIds: { type: Set, default: () => new Set() },
    filtered: Boolean,
});

defineEmits(['toggle', 'edit', 'delete', 'create', 'clear-filters', 'retry']);
</script>

<template>
    <div class="relative" :aria-busy="loading">
        <!-- first load: skeleton rows instead of an empty box -->
        <ul v-if="!loaded && loading" class="divide-y divide-slate-100" aria-label="Loading tasks">
            <li v-for="n in 4" :key="n" class="flex animate-pulse items-start gap-3 px-4 py-4 sm:px-5">
                <span class="size-5 rounded-full bg-slate-200" />
                <div class="flex-1 space-y-2">
                    <div class="h-3.5 w-2/5 rounded bg-slate-200" />
                    <div class="h-3 w-4/5 rounded bg-slate-100" />
                </div>
            </li>
        </ul>

        <div v-else-if="error && !tasks.length" class="flex flex-col items-center px-6 py-14 text-center">
            <AppIcon name="exclamation-triangle" class="size-10 text-rose-400" />
            <p class="mt-3 text-sm font-medium text-slate-900">We couldn't load your tasks</p>
            <p class="mt-1 text-sm text-slate-500">{{ error }}</p>
            <BaseButton variant="secondary" class="mt-4" @click="$emit('retry')">Try again</BaseButton>
        </div>

        <div v-else-if="!tasks.length" class="flex flex-col items-center px-6 py-14 text-center">
            <span class="flex size-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-500">
                <AppIcon :name="filtered ? 'search' : 'inbox'" class="size-6" />
            </span>
            <template v-if="filtered">
                <p class="mt-4 text-sm font-semibold text-slate-900">No tasks match your filters</p>
                <p class="mt-1 text-sm text-slate-500">Try a different search or status.</p>
                <BaseButton variant="secondary" class="mt-4" @click="$emit('clear-filters')">Clear filters</BaseButton>
            </template>
            <template v-else>
                <p class="mt-4 text-sm font-semibold text-slate-900">No tasks yet</p>
                <p class="mt-1 text-sm text-slate-500">Create your first task to get started.</p>
                <BaseButton class="mt-4" @click="$emit('create')">
                    <AppIcon name="plus" class="size-4" stroke-width="2.5" />
                    New task
                </BaseButton>
            </template>
        </div>

        <ul v-else class="divide-y divide-slate-100">
            <TaskItem
                v-for="task in tasks"
                :key="task.id"
                :task="task"
                :busy="busyIds.has(task.id)"
                @toggle="$emit('toggle', $event)"
                @edit="$emit('edit', $event)"
                @delete="$emit('delete', $event)"
            />
        </ul>

        <!-- refetch over existing rows: dim them rather than blanking the list -->
        <div v-if="loaded && loading" class="absolute inset-0 flex items-start justify-center bg-white/60 pt-10">
            <LoadingSpinner class="size-6 text-indigo-600" label="Loading tasks" />
        </div>
    </div>
</template>
