<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import { useAuth } from '@/composables/useAuth';
import { useToast } from '@/composables/useToast';

const router = useRouter();
const { register } = useAuth();
const toast = useToast();

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const errors = ref({});
const message = ref(null);
const processing = ref(false);

async function submit() {
    processing.value = true;
    errors.value = {};
    message.value = null;

    try {
        await register(form);
        toast.success('Your account is ready.');
        router.replace({ name: 'dashboard' });
    } catch (e) {
        errors.value = e.errors;

        if (!Object.keys(e.errors).length) {
            message.value = e.message;
        }

        form.password = '';
        form.password_confirmation = '';
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <AuthLayout title="Create your account" subtitle="Start organising your work in a minute.">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <p v-if="message" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert">{{ message }}</p>

            <BaseInput
                v-model="form.name"
                label="Name"
                name="name"
                autocomplete="name"
                :error="errors.name"
                :disabled="processing"
                autofocus
                required
            />

            <BaseInput
                v-model="form.email"
                label="Email address"
                type="email"
                name="email"
                autocomplete="email"
                :error="errors.email"
                :disabled="processing"
                required
            />

            <BaseInput
                v-model="form.password"
                label="Password"
                type="password"
                name="password"
                autocomplete="new-password"
                hint="At least 8 characters."
                :error="errors.password"
                :disabled="processing"
                required
            />

            <BaseInput
                v-model="form.password_confirmation"
                label="Confirm password"
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                :error="errors.password_confirmation"
                :disabled="processing"
                required
            />

            <BaseButton type="submit" block :loading="processing">Create account</BaseButton>
        </form>

        <template #footer>
            Already have an account?
            <RouterLink :to="{ name: 'login' }" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in</RouterLink>
        </template>
    </AuthLayout>
</template>
