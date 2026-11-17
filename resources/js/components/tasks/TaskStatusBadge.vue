<script setup>
defineProps({
    completed: { type: Boolean, required: true },
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
        :class="completed ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-amber-50 text-amber-700 ring-amber-600/20'"
    >
        <span class="size-1.5 rounded-full" :class="completed ? 'bg-emerald-500' : 'bg-amber-500'" aria-hidden="true" />
        {{ completed ? 'Completed' : 'Pending' }}
    </span>
</template>
