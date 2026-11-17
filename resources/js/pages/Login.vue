<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import { useAuth } from '@/composables/useAuth';
import { safeRedirect } from '@/utils/redirect';

const route = useRoute();
const router = useRouter();
const { login } = useAuth();

const form = reactive({ email: '', password: '', remember: false });
const errors = ref({});
const message = ref(null);
const processing = ref(false);

// seeded account; only advertised on a dev build
const showDemoHint = import.meta.env.DEV;

async function submit() {
    processing.value = true;
    errors.value = {};
    message.value = null;

    try {
        await login(form);
        router.replace(safeRedirect(route.query.redirect));
    } catch (e) {
        errors.value = e.errors;

        if (!Object.keys(e.errors).length) {
            message.value = e.message;
        }

        form.password = '';
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <AuthLayout title="Sign in to TaskFlow" subtitle="Welcome back. Pick up where you left off.">
        <form class="space-y-5" novalidate @submit.prevent="submit">
            <p v-if="message" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert">{{ message }}</p>

            <BaseInput
                v-model="form.email"
                label="Email address"
                type="email"
                name="email"
                autocomplete="email"
                :error="errors.email"
                :disabled="processing"
                autofocus
                required
            />

            <BaseInput
                v-model="form.password"
                label="Password"
                type="password"
                name="password"
                autocomplete="current-password"
                :error="errors.password"
                :disabled="processing"
                required
            />

            <label class="flex items-center gap-2.5">
                <input v-model="form.remember" type="checkbox" name="remember" class="size-4 rounded accent-indigo-600" />
                <span class="text-sm text-slate-600">Remember me</span>
            </label>

            <BaseButton type="submit" block :loading="processing">Sign in</BaseButton>

            <p v-if="showDemoHint" class="rounded-lg bg-slate-50 px-3 py-2 text-center text-xs text-slate-500">
                Demo account: <span class="font-medium text-slate-700">demo@example.com</span> /
                <span class="font-medium text-slate-700">password</span>
            </p>
        </form>

        <template #footer>
            New here?
            <RouterLink :to="{ name: 'register' }" class="font-medium text-indigo-600 hover:text-indigo-500">Create an account</RouterLink>
        </template>
    </AuthLayout>
</template>
