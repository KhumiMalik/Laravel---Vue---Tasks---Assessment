<script setup>
import AppIcon from '@/components/common/AppIcon.vue';
import TaskStatusBadge from './TaskStatusBadge.vue';
import { formatDateTime, formatRelative } from '@/utils/date';

defineProps({
    task: { type: Object, required: true },
    busy: Boolean,
});

defineEmits(['toggle', 'edit', 'delete']);

const iconButton =
    'rounded-lg p-2 text-slate-400 transition-colors focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:opacity-40';
</script>

<template>
    <li class="flex items-start gap-3 px-4 py-4 transition-colors hover:bg-slate-50/70 sm:px-5">
        <button
            type="button"
            role="checkbox"
            :aria-checked="task.is_completed"
            :aria-label="`Mark “${task.title}” as ${task.is_completed ? 'pending' : 'completed'}`"
            :disabled="busy"
            class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-wait"
            :class="task.is_completed ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 bg-white hover:border-indigo-500'"
            @click="$emit('toggle', task)"
        >
            <AppIcon v-if="task.is_completed" name="check" class="size-3" stroke-width="3" />
        </button>

        <div class="min-w-0 flex-1">
            <RouterLink
                :to="{ name: 'tasks.show', params: { id: task.id } }"
                class="block truncate rounded text-sm font-medium transition-colors hover:text-indigo-600 focus-visible:outline-2 focus-visible:outline-indigo-600"
                :class="task.is_completed ? 'text-slate-400 line-through decoration-slate-300' : 'text-slate-900'"
            >
                {{ task.title }}
            </RouterLink>

            <p v-if="task.description" class="mt-0.5 line-clamp-2 text-sm text-slate-500">{{ task.description }}</p>

            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                <TaskStatusBadge :completed="task.is_completed" />
                <time :datetime="task.created_at" :title="formatDateTime(task.created_at)">
                    Created {{ formatRelative(task.created_at) }}
                </time>
            </div>
        </div>

        <div class="flex shrink-0 items-center">
            <button
                type="button"
                :class="[iconButton, 'hover:bg-slate-100 hover:text-slate-700']"
                :aria-label="`Edit “${task.title}”`"
                title="Edit"
                :disabled="busy"
                @click="$emit('edit', task)"
            >
                <AppIcon name="pencil" class="size-4.5" />
            </button>
            <button
                type="button"
                :class="[iconButton, 'hover:bg-rose-50 hover:text-rose-600']"
                :aria-label="`Delete “${task.title}”`"
                title="Delete"
                :disabled="busy"
                @click="$emit('delete', task)"
            >
                <AppIcon name="trash" class="size-4.5" />
            </button>
        </div>
    </li>
</template>
