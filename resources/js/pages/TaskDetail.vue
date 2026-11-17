<script setup>
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AppIcon from '@/components/common/AppIcon.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseModal from '@/components/common/BaseModal.vue';
import LoadingSpinner from '@/components/common/LoadingSpinner.vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import DeleteConfirmation from '@/components/tasks/DeleteConfirmation.vue';
import TaskForm from '@/components/tasks/TaskForm.vue';
import TaskStatusBadge from '@/components/tasks/TaskStatusBadge.vue';
import { useToast } from '@/composables/useToast';
import taskService from '@/services/taskService';
import { formatDateTime, formatRelative } from '@/utils/date';

const props = defineProps({
    id: { type: Number, required: true },
});

const router = useRouter();
const toast = useToast();

const task = ref(null);
const loading = ref(true);
const error = ref(null);
const notFound = ref(false);
const toggling = ref(false);

async function load() {
    loading.value = true;
    error.value = null;
    notFound.value = false;

    try {
        task.value = await taskService.get(props.id);
    } catch (e) {
        task.value = null;

        // the API answers 403 for someone else's task; treat it the same as missing
        if (e.status === 404 || e.status === 403) {
            notFound.value = true;
        } else {
            error.value = e.message;
        }
    } finally {
        loading.value = false;
    }
}

// the same component instance is reused when hopping between tasks
watch(() => props.id, load, { immediate: true });

async function toggle() {
    toggling.value = true;

    try {
        task.value = await taskService.toggleCompletion(task.value.id);
        toast.success(task.value.is_completed ? 'Nice work, task completed.' : 'Task moved back to pending.');
    } catch (e) {
        toast.error(e.message);
    } finally {
        toggling.value = false;
    }
}

const editOpen = ref(false);
const formErrors = ref({});
const saving = ref(false);
const formKey = ref(0);

function openEdit() {
    formErrors.value = {};
    formKey.value++;
    editOpen.value = true;
}

async function save(payload) {
    saving.value = true;
    formErrors.value = {};

    try {
        task.value = await taskService.update(task.value.id, payload);
        editOpen.value = false;
        toast.success('Task updated.');
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

const deleteOpen = ref(false);
const deleting = ref(false);

async function destroy() {
    deleting.value = true;

    try {
        await taskService.remove(task.value.id);
        deleteOpen.value = false;
        toast.success('Task moved to trash.');
        router.push({ name: 'dashboard' });
    } catch (e) {
        toast.error(e.message);
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <AppLayout title="Task details">
        <RouterLink
            :to="{ name: 'dashboard' }"
            class="mb-5 inline-flex items-center gap-1.5 rounded text-sm font-medium text-slate-500 hover:text-slate-800 focus-visible:outline-2 focus-visible:outline-indigo-600"
        >
            <AppIcon name="arrow-left" class="size-4" />
            Back to tasks
        </RouterLink>

        <div v-if="loading" class="flex justify-center py-20">
            <LoadingSpinner class="size-7 text-indigo-600" label="Loading task" />
        </div>

        <div v-else-if="notFound" class="rounded-2xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-slate-900/5">
            <AppIcon name="inbox" class="mx-auto size-10 text-slate-300" />
            <p class="mt-3 text-sm font-semibold text-slate-900">Task not found</p>
            <p class="mt-1 text-sm text-slate-500">It may have been deleted, or the link is wrong.</p>
        </div>

        <div v-else-if="error" class="rounded-2xl bg-white px-6 py-16 text-center shadow-sm ring-1 ring-slate-900/5">
            <AppIcon name="exclamation-triangle" class="mx-auto size-10 text-rose-400" />
            <p class="mt-3 text-sm font-semibold text-slate-900">We couldn't load this task</p>
            <p class="mt-1 text-sm text-slate-500">{{ error }}</p>
            <BaseButton variant="secondary" class="mt-4" @click="load">Try again</BaseButton>
        </div>

        <article v-else class="max-w-3xl rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/5">
            <header class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6">
                <div class="min-w-0">
                    <TaskStatusBadge :completed="task.is_completed" />
                    <h2
                        class="mt-2 text-xl font-semibold break-words text-slate-900"
                        :class="{ 'text-slate-500 line-through decoration-slate-300': task.is_completed }"
                    >
                        {{ task.title }}
                    </h2>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <BaseButton variant="secondary" size="sm" :loading="toggling" @click="toggle">
                        <AppIcon :name="task.is_completed ? 'arrow-uturn-left' : 'check'" class="size-4" />
                        {{ task.is_completed ? 'Mark as pending' : 'Mark as completed' }}
                    </BaseButton>
                    <BaseButton variant="secondary" size="sm" :disabled="toggling" @click="openEdit">
                        <AppIcon name="pencil" class="size-4" />
                        Edit
                    </BaseButton>
                    <BaseButton variant="danger" size="sm" :disabled="toggling" @click="deleteOpen = true">
                        <AppIcon name="trash" class="size-4" />
                        Delete
                    </BaseButton>
                </div>
            </header>

            <div class="p-5 sm:p-6">
                <h3 class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Description</h3>
                <p v-if="task.description" class="mt-2 text-sm leading-relaxed whitespace-pre-line break-words text-slate-700">
                    {{ task.description }}
                </p>
                <p v-else class="mt-2 text-sm text-slate-400 italic">No description.</p>
            </div>

            <dl class="grid grid-cols-1 gap-4 border-t border-slate-100 p-5 text-sm sm:grid-cols-2 sm:p-6">
                <div>
                    <dt class="text-slate-500">Created</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <time :datetime="task.created_at" :title="formatRelative(task.created_at)">{{ formatDateTime(task.created_at) }}</time>
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Last updated</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <time :datetime="task.updated_at" :title="formatRelative(task.updated_at)">{{ formatDateTime(task.updated_at) }}</time>
                    </dd>
                </div>
            </dl>
        </article>

        <BaseModal v-model:open="editOpen" title="Edit task" :closable="!saving">
            <TaskForm
                v-if="task"
                :key="formKey"
                :task="task"
                :errors="formErrors"
                :processing="saving"
                @submit="save"
                @cancel="editOpen = false"
            />
        </BaseModal>

        <DeleteConfirmation
            v-model:open="deleteOpen"
            :message="`“${task?.title ?? ''}” will be moved to the trash. You can restore it from there.`"
            confirm-label="Move to trash"
            :processing="deleting"
            @confirm="destroy"
        />
    </AppLayout>
</template>
