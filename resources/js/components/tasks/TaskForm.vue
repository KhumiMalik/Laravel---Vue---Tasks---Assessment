<script setup>
import { computed, reactive, ref } from 'vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';

// keep in sync with StoreTaskRequest
const TITLE_MAX = 255;
const DESCRIPTION_MAX = 5000;

const props = defineProps({
    task: { type: Object, default: null },
    errors: { type: Object, default: () => ({}) },
    processing: Boolean,
});

const emit = defineEmits(['submit', 'cancel']);

const isEditing = computed(() => props.task !== null);

const form = reactive({
    title: props.task?.title ?? '',
    description: props.task?.description ?? '',
    is_completed: props.task?.is_completed ?? false,
});

const localErrors = ref({});

// a client-side error wins until the user resubmits; otherwise show what the API said
const fieldError = (field) => localErrors.value[field] ?? props.errors[field] ?? null;

const descriptionHint = computed(() => `${form.description.length.toLocaleString()} / ${DESCRIPTION_MAX.toLocaleString()} characters`);

function submit() {
    localErrors.value = {};

    const title = form.title.trim();
    const description = form.description.trim();

    if (!title) {
        localErrors.value.title = 'The title field is required.';
        return;
    }

    const payload = {
        title,
        description: description === '' ? null : description,
    };

    if (isEditing.value) {
        payload.is_completed = form.is_completed;
    }

    emit('submit', payload);
}
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="submit">
        <BaseInput
            v-model="form.title"
            label="Title"
            name="title"
            placeholder="What needs to be done?"
            :maxlength="TITLE_MAX"
            :error="fieldError('title')"
            :disabled="processing"
            autocomplete="off"
            autofocus
            required
        />

        <BaseInput
            v-model="form.description"
            label="Description"
            name="description"
            placeholder="Add some detail (optional)"
            rows="4"
            multiline
            :maxlength="DESCRIPTION_MAX"
            :hint="descriptionHint"
            :error="fieldError('description')"
            :disabled="processing"
        />

        <div v-if="isEditing">
            <label class="flex items-center gap-3">
                <input
                    v-model="form.is_completed"
                    type="checkbox"
                    name="is_completed"
                    class="size-4 rounded border-slate-300 text-indigo-600 accent-indigo-600 focus:ring-indigo-600"
                    :disabled="processing"
                />
                <span class="text-sm font-medium text-slate-700">Mark as completed</span>
            </label>
            <p v-if="fieldError('is_completed')" class="mt-1.5 text-sm text-rose-600">{{ fieldError('is_completed') }}</p>
        </div>

        <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
            <BaseButton variant="secondary" :disabled="processing" @click="emit('cancel')">Cancel</BaseButton>
            <BaseButton type="submit" :loading="processing">
                {{ isEditing ? 'Save changes' : 'Create task' }}
            </BaseButton>
        </div>
    </form>
</template>
