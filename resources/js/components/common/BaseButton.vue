<script setup>
import { computed } from 'vue';
import LoadingSpinner from './LoadingSpinner.vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger', 'ghost', 'ghost-danger'].includes(value),
    },
    size: { type: String, default: 'md' },
    type: { type: String, default: 'button' },
    loading: Boolean,
    disabled: Boolean,
    block: Boolean,
});

const VARIANTS = {
    primary: 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-indigo-600',
    secondary: 'bg-white text-slate-700 shadow-sm ring-1 ring-slate-300 ring-inset hover:bg-slate-50 focus-visible:outline-indigo-600',
    danger: 'bg-rose-600 text-white shadow-sm hover:bg-rose-500 focus-visible:outline-rose-600',
    ghost: 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-indigo-600',
    'ghost-danger': 'text-rose-600 hover:bg-rose-50 hover:text-rose-700 focus-visible:outline-rose-600',
};

const SIZES = {
    sm: 'gap-1.5 px-2.5 py-1.5 text-xs',
    md: 'gap-2 px-3.5 py-2 text-sm',
};

const classes = computed(() => [
    'inline-flex items-center justify-center rounded-lg font-semibold transition-colors',
    'focus-visible:outline-2 focus-visible:outline-offset-2',
    'disabled:cursor-not-allowed disabled:opacity-60',
    VARIANTS[props.variant],
    SIZES[props.size],
    props.block && 'w-full',
]);
</script>

<template>
    <button :type="type" :class="classes" :disabled="disabled || loading" :aria-busy="loading || undefined">
        <LoadingSpinner v-if="loading" class="size-4" label="Working" />
        <slot />
    </button>
</template>
