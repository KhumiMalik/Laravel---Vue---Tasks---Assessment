<script setup>
import AppIcon from '@/components/common/AppIcon.vue';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
});
</script>

<template>
    <div class="flex min-h-full flex-col justify-center bg-gradient-to-b from-indigo-50/70 to-slate-50 px-4 py-12">
        <div class="mx-auto w-full max-w-sm">
            <div class="flex flex-col items-center text-center">
                <span class="flex size-11 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm">
                    <AppIcon name="check" class="size-6" stroke-width="2.5" />
                </span>
                <h1 class="mt-5 text-2xl font-semibold tracking-tight text-slate-900">{{ title }}</h1>
                <p v-if="subtitle" class="mt-1.5 text-sm text-slate-500">{{ subtitle }}</p>
            </div>

            <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 sm:p-8">
                <slot />
            </div>

            <div v-if="$slots.footer" class="mt-6 text-center text-sm text-slate-500">
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
