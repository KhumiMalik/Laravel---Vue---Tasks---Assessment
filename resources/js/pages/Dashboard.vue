<script setup>
import { computed, onMounted, ref } from 'vue';
import AppIcon from '@/components/common/AppIcon.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseModal from '@/components/common/BaseModal.vue';
import PaginationNav from '@/components/common/PaginationNav.vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import DeleteConfirmation from '@/components/tasks/DeleteConfirmation.vue';
import RecentActivity from '@/components/tasks/RecentActivity.vue';
import TaskFilters from '@/components/tasks/TaskFilters.vue';
import TaskForm from '@/components/tasks/TaskForm.vue';
import TaskList from '@/components/tasks/TaskList.vue';
import TaskStatistics from '@/components/tasks/TaskStatistics.vue';
import { useAuth } from '@/composables/useAuth';
import { useTasks } from '@/composables/useTasks';
import { useToast } from '@/composables/useToast';

const { user } = useAuth();
const toast = useToast();
const {
    tasks,
    meta,
    statistics,
    activities,
    filters,
    loading,
    loaded,
    error,
    busyIds,
    hasActiveFilters,
    statusCounts,
    load,
    fetchTasks,
    clearFilters,
    createTask,
    updateTask,
    toggleTask,
    deleteTask,
    restoreTask,
} = useTasks();

const firstName = computed(() => user.value?.name.split(' ')[0] ?? '');

// create / edit share one modal; editingTask decides which
const formOpen = ref(false);
const editingTask = ref(null);
const formErrors = ref({});
const saving = ref(false);
// remounts TaskForm so it picks up the right initial values every time it opens
const formKey = ref(0);

function openCreate() {
    editingTask.value = null;
    formErrors.value = {};
    formKey.value++;
    formOpen.value = true;
}

function openEdit(task) {
    editingTask.value = task;
    formErrors.value = {};
    formKey.value++;
    formOpen.value = true;
}

async function saveTask(payload) {
    saving.value = true;
    formErrors.value = {};

    try {
        if (editingTask.value) {
            await updateTask(editingTask.value.id, payload);
            toast.success('Task updated.');
        } else {
            await createTask(payload);
            toast.success('Task created.');
        }

        formOpen.value = false;
    } catch (e) {
        if (e.status === 422) {
            formErrors.value = e.errors;
        } else {
            toast.error(e.message);
        }
    } finally {
        saving.value = false;
    }
}

async function toggle(task) {
    try {
        const updated = await toggleTask(task);

        if (!updated) {
            return;
        }

        toast.success(updated.is_completed ? 'Nice work, task completed.' : 'Task moved back to pending.');
    } catch (e) {
        toast.error(e.message);
    }
}

const deleteTarget = ref(null);
const deleteOpen = ref(false);
const deleting = ref(false);

function confirmDelete(task) {
    deleteTarget.value = task;
    deleteOpen.value = true;
}

async function destroy() {
    const task = deleteTarget.value;
    deleting.value = true;

    try {
        await deleteTask(task.id);
        deleteOpen.value = false;

        // soft delete, so undo is just a restore
        toast.success('Task moved to trash.', {
            timeout: 6000,
            action: { label: 'Undo', handler: () => undoDelete(task) },
        });
    } catch (e) {
        toast.error(e.message);
    } finally {
        deleting.value = false;
    }
}

async function undoDelete(task) {
    try {
        await restoreTask(task.id);
        toast.success('Task restored.');
    } catch (e) {
        toast.error(e.message);
    }
}

onMounted(load);
</script>

<template>
    <AppLayout title="My tasks" :subtitle="firstName ? `Welcome back, ${firstName}.` : null">
        <template #actions>
            <BaseButton @click="openCreate">
                <AppIcon name="plus" class="size-4" stroke-width="2.5" />
                <span class="hidden sm:inline">New task</span>
                <span class="sm:hidden">New</span>
            </BaseButton>
        </template>

        <div class="space-y-6">
            <TaskStatistics :statistics="statistics" />

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <section class="min-w-0 space-y-4" aria-label="Tasks">
                    <TaskFilters v-model:search="filters.search" v-model:status="filters.status" :counts="statusCounts" />

                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/5">
                        <TaskList
                            :tasks="tasks"
                            :loading="loading"
                            :loaded="loaded"
                            :error="error"
                            :busy-ids="busyIds"
                            :filtered="hasActiveFilters"
                            @toggle="toggle"
                            @edit="openEdit"
                            @delete="confirmDelete"
                            @create="openCreate"
                            @clear-filters="clearFilters"
                            @retry="fetchTasks"
                        />
                    </div>

                    <PaginationNav v-if="meta && meta.total > 0" :meta="meta" :disabled="loading" @change="filters.page = $event" />
                </section>

                <aside class="min-w-0">
                    <RecentActivity :activities="activities" />
                </aside>
            </div>
        </div>

        <BaseModal
            v-model:open="formOpen"
            :title="editingTask ? 'Edit task' : 'New task'"
            :description="editingTask ? null : 'Give it a clear title. You can add details if it helps.'"
            :closable="!saving"
        >
            <TaskForm
                :key="formKey"
                :task="editingTask"
                :errors="formErrors"
                :processing="saving"
                @submit="saveTask"
                @cancel="formOpen = false"
            />
        </BaseModal>

        <DeleteConfirmation
            v-model:open="deleteOpen"
            :message="`“${deleteTarget?.title ?? ''}” will be moved to the trash. You can restore it from there.`"
            confirm-label="Move to trash"
            :processing="deleting"
            @confirm="destroy"
        />
    </AppLayout>
</template>
