<script setup>
import { ref } from 'vue';
import AppHeader from './AppHeader.vue';
import Sidebar from './Sidebar.vue';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
});

const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-full">
        <Sidebar v-model:open="sidebarOpen" />

        <div class="lg:pl-64">
            <AppHeader :title="title" :subtitle="subtitle" @open-menu="sidebarOpen = true">
                <template v-if="$slots.actions" #actions>
                    <slot name="actions" />
                </template>
            </AppHeader>

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
