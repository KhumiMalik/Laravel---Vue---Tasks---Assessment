<script setup>
import { computed, useId, useSlots } from 'vue';

// attrs (type, autocomplete, maxlength...) go on the input, not the wrapper div
defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number, null], default: '' });

const props = defineProps({
    label: { type: String, required: true },
    error: { type: [String, Array], default: null },
    hint: { type: String, default: null },
    multiline: Boolean,
    hideLabel: Boolean,
});

const slots = useSlots();
const id = useId();

const errorMessage = computed(() => (Array.isArray(props.error) ? props.error[0] : props.error));

const describedBy = computed(
    () =>
        [errorMessage.value && `${id}-error`, props.hint && !errorMessage.value && `${id}-hint`]
            .filter(Boolean)
            .join(' ') || undefined,
);

const fieldClasses = computed(() => [
    'block w-full rounded-lg border-0 bg-white py-2 text-sm text-slate-900 shadow-sm ring-1 ring-inset',
    'placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:outline-none',
    'disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500',
    errorMessage.value ? 'ring-rose-400 focus:ring-rose-500' : 'ring-slate-300 focus:ring-indigo-600',
    slots.leading ? 'pl-9 pr-3' : 'px-3',
    props.multiline && 'resize-y',
]);
</script>

<template>
    <div>
        <label :for="id" :class="hideLabel ? 'sr-only' : 'mb-1.5 block text-sm font-medium text-slate-700'">
            {{ label }}
        </label>

        <div class="relative">
            <span v-if="$slots.leading" class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <slot name="leading" />
            </span>

            <!-- :value/@input rather than v-model: v-model on <component :is> doesn't bind native fields -->
            <component
                :is="multiline ? 'textarea' : 'input'"
                :id="id"
                v-bind="$attrs"
                :value="model ?? ''"
                :class="fieldClasses"
                :aria-invalid="errorMessage ? 'true' : undefined"
                :aria-describedby="describedBy"
                @input="model = $event.target.value"
            />

            <span v-if="$slots.trailing" class="absolute inset-y-0 right-0 flex items-center pr-2">
                <slot name="trailing" />
            </span>
        </div>

        <p v-if="errorMessage" :id="`${id}-error`" class="mt-1.5 text-sm text-rose-600">{{ errorMessage }}</p>
        <p v-else-if="hint" :id="`${id}-hint`" class="mt-1.5 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
