<script setup>
import { useToast } from '@/composables/useToast';
import AppIcon from './AppIcon.vue';

const { toasts, dismiss } = useToast();

const STYLES = {
    success: { icon: 'check-circle', iconClass: 'text-emerald-500' },
    error: { icon: 'x-circle', iconClass: 'text-rose-500' },
};

function runAction(toast) {
    dismiss(toast.id);
    toast.action.handler();
}
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6"
            aria-live="polite"
            aria-atomic="false"
        >
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :role="toast.type === 'error' ? 'alert' : 'status'"
                    class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-white p-4 shadow-lg ring-1 ring-slate-900/5"
                >
                    <AppIcon :name="STYLES[toast.type].icon" class="size-5 shrink-0" :class="STYLES[toast.type].iconClass" />
                    <p class="flex-1 text-sm font-medium text-slate-800">{{ toast.message }}</p>
                    <button
                        v-if="toast.action"
                        type="button"
                        class="shrink-0 rounded text-sm font-semibold text-indigo-600 hover:text-indigo-500 focus-visible:outline-2 focus-visible:outline-indigo-600"
                        @click="runAction(toast)"
                    >
                        {{ toast.action.label }}
                    </button>
                    <button
                        type="button"
                        class="shrink-0 rounded text-slate-400 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-indigo-600"
                        aria-label="Dismiss notification"
                        @click="dismiss(toast.id)"
                    >
                        <AppIcon name="x-mark" class="size-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
