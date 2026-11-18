<script setup>
import { ref, watch } from 'vue';
import AppIcon from '@/components/common/AppIcon.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import LoadingSpinner from '@/components/common/LoadingSpinner.vue';
import AppLayout from '@/components/layout/AppLayout.vue';
import WeatherCard from '@/components/weather/WeatherCard.vue';
import weatherService from '@/services/weatherService';

const city = ref('');
const fieldError = ref(null);
const loading = ref(false);
const weather = ref(null);
const error = ref(null);
const lastQuery = ref('');

// the validation message is about what was typed, so it goes once they start typing again
watch(city, () => (fieldError.value = null));

// The shared axios handler swaps every 5xx message for a generic one, which is right for
// our own errors but loses "the weather service is down" vs "it timed out". Pick by status here.
function describeError(e) {
    if (e.status === 404) {
        return { title: 'City not found', message: e.message, retry: false };
    }

    if (e.status === 504) {
        return { title: 'Weather service timed out', message: 'Open-Meteo took too long to respond. Please try again.', retry: true };
    }

    if (e.status >= 500) {
        return { title: 'Weather service unavailable', message: "We couldn't get the weather right now. Please try again in a moment.", retry: true };
    }

    // 0 = no response at all (offline); 429 and anything else already carry a readable message
    return { title: "Couldn't load the weather", message: e.message, retry: e.status === 0 };
}

async function search(query) {
    if (loading.value) return;

    loading.value = true;
    lastQuery.value = query;
    fieldError.value = null;
    error.value = null;
    // drop the previous result so it can't be mistaken for this search's
    weather.value = null;

    try {
        weather.value = await weatherService.current(query);
    } catch (e) {
        if (e.status === 422) {
            fieldError.value = e.errors.city?.[0] ?? e.message;
        } else {
            error.value = describeError(e);
        }
    } finally {
        loading.value = false;
    }
}

function submit() {
    const query = city.value.trim().replace(/\s+/g, ' ');

    if (!query) {
        fieldError.value = 'Please enter a city name.';
        return;
    }

    search(query);
}
</script>

<template>
    <AppLayout title="Weather" subtitle="Current conditions for any city, powered by Open-Meteo.">
        <div class="mx-auto max-w-3xl space-y-5">
            <form
                class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5 sm:p-5"
                role="search"
                novalidate
                @submit.prevent="submit"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <!-- BaseInput puts attrs (incl. class) on the <input>, so size it from outside -->
                    <div class="flex-1">
                        <BaseInput
                            v-model="city"
                            label="City"
                            hide-label
                            name="city"
                            placeholder="Search a city, e.g. Lahore"
                            autocomplete="off"
                            enterkeyhint="search"
                            maxlength="100"
                            :error="fieldError"
                        >
                            <template #leading>
                                <AppIcon name="search" class="size-4" />
                            </template>
                        </BaseInput>
                    </div>

                    <BaseButton type="submit" :loading="loading" class="sm:min-w-28">
                        <template v-if="!loading">
                            <AppIcon name="search" class="size-4" />
                        </template>
                        {{ loading ? 'Searching…' : 'Search' }}
                    </BaseButton>
                </div>
            </form>

            <section aria-live="polite" :aria-busy="loading">
                <div
                    v-if="loading"
                    class="flex flex-col items-center rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-900/5"
                >
                    <LoadingSpinner class="size-7 text-indigo-600" label="Loading weather" />
                    <p class="mt-3 text-sm text-slate-500">Checking the weather in {{ lastQuery }}…</p>
                </div>

                <div
                    v-else-if="error"
                    class="flex flex-col items-center rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-slate-900/5"
                    role="alert"
                >
                    <AppIcon
                        :name="error.retry ? 'exclamation-triangle' : 'map-pin'"
                        class="size-10"
                        :class="error.retry ? 'text-amber-500' : 'text-slate-300'"
                    />
                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ error.title }}</p>
                    <p class="mt-1 max-w-md text-sm text-slate-500">{{ error.message }}</p>
                    <BaseButton v-if="error.retry" variant="secondary" class="mt-4" @click="search(lastQuery)">Try again</BaseButton>
                </div>

                <WeatherCard v-else-if="weather" :weather="weather" />

                <div v-else class="flex flex-col items-center rounded-2xl border-2 border-dashed border-slate-200 px-6 py-14 text-center">
                    <span class="flex size-12 items-center justify-center rounded-full bg-sky-50 text-sky-500">
                        <AppIcon name="sun" class="size-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-slate-900">Check the weather anywhere</p>
                    <p class="mt-1 text-sm text-slate-500">Search for a city to see its current temperature, wind and humidity.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
