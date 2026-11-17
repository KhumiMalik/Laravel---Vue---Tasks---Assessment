<script setup>
import AppIcon from '@/components/common/AppIcon.vue';
import { formatDateTime, formatRelative } from '@/utils/date';

defineProps({
    activities: { type: Array, required: true },
});
</script>

<template>
    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/5" aria-labelledby="recent-activity-heading">
        <h2 id="recent-activity-heading" class="border-b border-slate-100 px-5 py-4 text-sm font-semibold text-slate-900">
            Recent activity
        </h2>

        <p v-if="!activities.length" class="px-5 py-8 text-center text-sm text-slate-500">
            Completed tasks will show up here.
        </p>

        <ol v-else class="space-y-4 px-5 py-4">
            <li v-for="activity in activities" :key="activity.id" class="flex gap-3">
                <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <AppIcon name="check" class="size-3.5" stroke-width="2.5" />
                </span>
                <div class="min-w-0 text-sm">
                    <p class="text-slate-600">
                        Completed
                        <RouterLink
                            v-if="activity.task && !activity.task.is_trashed"
                            :to="{ name: 'tasks.show', params: { id: activity.task.id } }"
                            class="font-medium text-slate-900 hover:text-indigo-600"
                        >
                            {{ activity.task.title }}
                        </RouterLink>
                        <span v-else-if="activity.task" class="font-medium text-slate-500">
                            {{ activity.task.title }} <span class="font-normal">(in trash)</span>
                        </span>
                    </p>
                    <time class="text-xs text-slate-400" :datetime="activity.created_at" :title="formatDateTime(activity.created_at)">
                        {{ formatRelative(activity.created_at) }}
                    </time>
                </div>
            </li>
        </ol>
    </section>
</template>
