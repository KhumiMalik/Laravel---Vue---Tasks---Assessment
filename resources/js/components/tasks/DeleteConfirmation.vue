<script setup>
import AppIcon from '@/components/common/AppIcon.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseModal from '@/components/common/BaseModal.vue';

const open = defineModel('open', { type: Boolean, default: false });

defineProps({
    title: { type: String, default: 'Delete task?' },
    message: { type: String, required: true },
    confirmLabel: { type: String, default: 'Delete' },
    processing: Boolean,
});

defineEmits(['confirm']);
</script>

<template>
    <BaseModal v-model:open="open" :title="title" size="sm" :closable="!processing">
        <div class="flex gap-4">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                <AppIcon name="exclamation-triangle" class="size-5" />
            </span>
            <p class="text-sm text-slate-600">{{ message }}</p>
        </div>

        <template #footer>
            <BaseButton variant="secondary" :disabled="processing" @click="open = false">Cancel</BaseButton>
            <BaseButton variant="danger" :loading="processing" @click="$emit('confirm')">{{ confirmLabel }}</BaseButton>
        </template>
    </BaseModal>
</template>
