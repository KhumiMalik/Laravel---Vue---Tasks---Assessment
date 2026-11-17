<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useToast } from '@/composables/useToast';
import AppIcon from '@/components/common/AppIcon.vue';

const open = defineModel('open', { type: Boolean, default: false });

const route = useRoute();
const router = useRouter();
const { user, logout } = useAuth();
const toast = useToast();

const signingOut = ref(false);

const NAV = [
    { label: 'My tasks', icon: 'check-circle', to: { name: 'dashboard' }, matches: ['dashboard', 'tasks.show'] },
    { label: 'Trash', icon: 'trash', to: { name: 'trash' }, matches: ['trash'] },
];

const initials = computed(() =>
    (user.value?.name ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join(''),
);

// close the mobile drawer after navigating
watch(() => route.fullPath, () => (open.value = false));

async function signOut() {
    signingOut.value = true;

    try {
        await logout();
        router.push({ name: 'login' });
    } catch (e) {
        toast.error(e.message);
    } finally {
        signingOut.value = false;
    }
}
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-150"
        leave-to-class="opacity-0"
    >
        <div v-if="open" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" aria-hidden="true" @click="open = false" />
    </Transition>

    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-[translate,visibility] duration-200 lg:visible lg:translate-x-0"
        :class="open ? 'translate-x-0 shadow-xl' : 'invisible -translate-x-full'"
        aria-label="Main navigation"
        @keydown.esc="open = false"
    >
        <div class="flex h-16 items-center justify-between px-5">
            <RouterLink :to="{ name: 'dashboard' }" class="flex items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-indigo-600">
                <span class="flex size-8 items-center justify-center rounded-lg bg-indigo-600 text-white">
                    <AppIcon name="check" class="size-5" stroke-width="2.5" />
                </span>
                <span class="text-lg font-semibold tracking-tight text-slate-900">TaskFlow</span>
            </RouterLink>

            <button
                type="button"
                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 lg:hidden"
                aria-label="Close navigation"
                @click="open = false"
            >
                <AppIcon name="x-mark" class="size-5" />
            </button>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-4">
            <RouterLink
                v-for="item in NAV"
                :key="item.label"
                :to="item.to"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-indigo-600"
                :class="item.matches.includes(route.name) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
                :aria-current="item.matches.includes(route.name) ? 'page' : undefined"
            >
                <AppIcon :name="item.icon" class="size-5" />
                {{ item.label }}
            </RouterLink>
        </nav>

        <div class="border-t border-slate-200 p-3">
            <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700" aria-hidden="true">
                    {{ initials }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-900">{{ user?.name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ user?.email }}</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:opacity-50"
                    aria-label="Sign out"
                    title="Sign out"
                    :disabled="signingOut"
                    @click="signOut"
                >
                    <AppIcon name="logout" class="size-5" />
                </button>
            </div>
        </div>
    </aside>
</template>
