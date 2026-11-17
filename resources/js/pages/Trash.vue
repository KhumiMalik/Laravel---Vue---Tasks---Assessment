<script setup>
import { onMounted, ref } from 'vue';
import AppIcon from '@/components/common/AppIcon.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import LoadingSpinner from '@/components/common/LoadingSpinner.vue';
import PaginationNav from '@/components/common/PaginationNav.vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import DeleteConfirmation from '@/components/tasks/DeleteConfirmation.vue';
import { useToast } from '@/composables/useToast';
import taskService from '@/services/taskService';
import { formatDateTime, formatRelative } from '@/utils/date';

const toast = useToast();

const tasks = ref([]);
const meta = ref(null);
const page = ref(1);
const loading = ref(false);
const loaded = ref(false);
const error = ref(null);
const busyId = ref(null);

async function load() {
    loading.value = true;
    error.value = null;

    try {
        const response = await taskService.trash({ page: page.value });

        // emptied the last page: step back one
        if (response.data.length === 0 && page.value > 1) {
            page.value = response.meta.last_page;
            return load();
        }

        tasks.value = response.data;
        meta.value = response.meta;
        loaded.value = true;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}

function goTo(next) {
    page.value = next;
    load();
}

async function restore(task) {
    busyId.value = task.id;

    try {
        await taskService.restore(task.id);
        toast.success(`“${task.title}” restored.`);
        await load();
    } catch (e) {
        toast.error(e.message);
    } finally {
        busyId.value = null;
    }
}

const deleteTarget = ref(null);
const deleteOpen = ref(false);
const deleting = ref(false);

function confirmForceDelete(task) {
    deleteTarget.value = task;
    deleteOpen.value = true;
}

async function forceDelete() {
    deleting.value = true;

    try {
        await taskService.forceDelete(deleteTarget.value.id);
        deleteOpen.value = false;
        toast.success('Task permanently deleted.');
        await load();
    } catch (e) {
        toast.error(e.message);
    } finally {
        deleting.value = false;
    }
}

onMounted(load);
</script>

<template>
    <AppLayout title="Trash" subtitle="Deleted tasks stay here until you restore them or delete them for good.">
        <div class="space-y-4">
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/5">
                <div v-if="!loaded && loading" class="flex justify-center py-16">
                    <LoadingSpinner class="size-7 text-indigo-600" label="Loading trash" />
                </div>

                <div v-else-if="error && !tasks.length" class="flex flex-col items-center px-6 py-14 text-center">
                    <AppIcon name="exclamation-triangle" class="size-10 text-rose-400" />
                    <p class="mt-3 text-sm font-medium text-slate-900">We couldn't load the trash</p>
                    <p class="mt-1 text-sm text-slate-500">{{ error }}</p>
                    <BaseButton variant="secondary" class="mt-4" @click="load">Try again</BaseButton>
                </div>

                <div v-else-if="!tasks.length" class="flex flex-col items-center px-6 py-14 text-center">
                    <span class="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <AppIcon name="trash" class="size-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-slate-900">Trash is empty</p>
                    <p class="mt-1 text-sm text-slate-500">Tasks you delete will show up here.</p>
                </div>

                <ul v-else class="divide-y divide-slate-100" :aria-busy="loading">
                    <li
                        v-for="task in tasks"
                        :key="task.id"
                        class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-700">{{ task.title }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Deleted
                                <time :datetime="task.deleted_at" :title="formatDateTime(task.deleted_at)">{{ formatRelative(task.deleted_at) }}</time>
                                <span v-if="task.is_completed"> · was completed</span>
                            </p>
                        </div>

                        <div class="flex shrink-0 gap-2">
                            <BaseButton
                                variant="secondary"
                                size="sm"
                                :loading="busyId === task.id"
                                :disabled="busyId !== null && busyId !== task.id"
                                @click="restore(task)"
                            >
                                <AppIcon name="arrow-uturn-left" class="size-4" />
                                Restore
                            </BaseButton>
                            <BaseButton variant="ghost-danger" size="sm" :disabled="busyId !== null" @click="confirmForceDelete(task)">
                                Delete forever
                            </BaseButton>
                        </div>
                    </li>
                </ul>
            </div>

            <PaginationNav v-if="meta && meta.total > 0" :meta="meta" :disabled="loading" @change="goTo" />
        </div>

        <DeleteConfirmation
            v-model:open="deleteOpen"
            title="Delete permanently?"
            :message="`“${deleteTarget?.title ?? ''}” and its activity history will be deleted. This can't be undone.`"
            confirm-label="Delete forever"
            :processing="deleting"
            @confirm="forceDelete"
        />
    </AppLayout>
</template>
