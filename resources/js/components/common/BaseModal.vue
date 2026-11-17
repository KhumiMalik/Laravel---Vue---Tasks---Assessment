<script setup>
import { computed, nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import AppIcon from './AppIcon.vue';

const open = defineModel('open', { type: Boolean, default: false });

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    size: { type: String, default: 'md' },
    // false while a request is in flight so the user can't close mid-save
    closable: { type: Boolean, default: true },
});

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

const panel = ref(null);
const titleId = useId();
const descriptionId = useId();
let returnFocusTo = null;

const widthClass = computed(() => ({ sm: 'sm:max-w-md', md: 'sm:max-w-lg', lg: 'sm:max-w-2xl' })[props.size]);

function close() {
    if (props.closable) {
        open.value = false;
    }
}

watch(open, async (isOpen) => {
    if (isOpen) {
        returnFocusTo = document.activeElement;
        document.body.style.overflow = 'hidden';
        await nextTick();
        const target = panel.value?.querySelector('[autofocus]') ?? panel.value?.querySelector(FOCUSABLE);
        target?.focus();
    } else {
        document.body.style.overflow = '';
        returnFocusTo?.focus?.();
    }
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});

// keep Tab inside the dialog while it's open
function onKeydown(event) {
    if (event.key === 'Escape') {
        event.stopPropagation();
        close();
        return;
    }

    if (event.key !== 'Tab' || !panel.value) {
        return;
    }

    const nodes = [...panel.value.querySelectorAll(FOCUSABLE)];

    if (!nodes.length) {
        return;
    }

    const first = nodes[0];
    const last = nodes[nodes.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-0 backdrop-blur-[2px] sm:items-center sm:p-4"
                @mousedown.self="close"
                @keydown="onKeydown"
            >
                <div
                    ref="panel"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="titleId"
                    :aria-describedby="description ? descriptionId : undefined"
                    class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white shadow-xl sm:rounded-2xl"
                    :class="widthClass"
                >
                    <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 :id="titleId" class="text-base font-semibold text-slate-900">{{ title }}</h2>
                            <p v-if="description" :id="descriptionId" class="mt-1 text-sm text-slate-500">
                                {{ description }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="-m-1.5 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                            aria-label="Close dialog"
                            :disabled="!closable"
                            @click="close"
                        >
                            <AppIcon name="x-mark" class="size-5" />
                        </button>
                    </header>

                    <div class="px-5 py-5">
                        <slot />
                    </div>

                    <footer
                        v-if="$slots.footer"
                        class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/70 px-5 py-3 sm:flex-row sm:justify-end"
                    >
                        <slot name="footer" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
