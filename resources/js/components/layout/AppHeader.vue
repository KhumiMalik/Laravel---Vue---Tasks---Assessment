<script setup>
import AppIcon from '@/components/common/AppIcon.vue';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
});

defineEmits(['open-menu']);
</script>

<template>
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/85 backdrop-blur">
        <div class="flex min-h-16 items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
            <button
                type="button"
                class="-ml-1 rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-indigo-600 lg:hidden"
                aria-label="Open navigation"
                @click="$emit('open-menu')"
            >
                <AppIcon name="bars" class="size-5" />
            </button>

            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-semibold text-slate-900 sm:text-xl">{{ title }}</h1>
                <p v-if="subtitle" class="truncate text-sm text-slate-500">{{ subtitle }}</p>
            </div>

            <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
