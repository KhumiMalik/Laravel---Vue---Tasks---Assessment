<script setup>
import { computed } from 'vue';
import AppIcon from '@/components/common/AppIcon.vue';

const props = defineProps({
    weather: { type: Object, required: true },
});

// WMO code ranges -> glyph. The backend already sends the readable condition text.
function iconFor(code, isDay) {
    if (code <= 1) return isDay ? 'sun' : 'moon';
    if (code <= 3) return 'cloud';
    if (code <= 48) return 'cloud-fog';
    if (code <= 57) return 'cloud-drizzle';
    if (code <= 67 || (code >= 80 && code <= 82)) return 'cloud-rain';
    if (code <= 77 || code === 85 || code === 86) return 'cloud-snow';
    if (code >= 95) return 'cloud-bolt';
    return 'cloud';
}

function format(value, unit, digits = 1) {
    if (value === null || value === undefined) return '—';
    return `${Number(value).toFixed(digits).replace(/\.0+$/, '')}${unit}`;
}

const icon = computed(() => iconFor(props.weather.weather_code, props.weather.is_day));

const place = computed(() => [props.weather.region, props.weather.country].filter(Boolean).join(', '));

// "2026-11-17T12:15" is already local time at the city (timezone=auto), so just show the clock part
const observedAt = computed(() => props.weather.observed_at?.slice(11, 16) ?? null);

const details = computed(() => [
    { label: 'Feels like', icon: 'thermometer', value: format(props.weather.feels_like, '°C') },
    { label: 'Humidity', icon: 'drop', value: format(props.weather.humidity, '%', 0) },
    { label: 'Wind', icon: 'wind', value: format(props.weather.wind_speed, ' km/h') },
    { label: 'Precipitation', icon: 'cloud-rain', value: format(props.weather.precipitation, ' mm') },
]);
</script>

<template>
    <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-900/5" aria-labelledby="weather-city">
        <div
            class="flex flex-col gap-6 p-5 text-white sm:flex-row sm:items-center sm:justify-between sm:p-6"
            :class="weather.is_day ? 'bg-gradient-to-br from-sky-500 to-indigo-600' : 'bg-gradient-to-br from-slate-700 to-indigo-950'"
        >
            <div class="min-w-0">
                <h2 id="weather-city" class="flex items-center gap-1.5 text-lg font-semibold">
                    <AppIcon name="map-pin" class="size-5 shrink-0 opacity-80" />
                    <span class="truncate">{{ weather.city }}</span>
                </h2>
                <p v-if="place" class="mt-0.5 truncate text-sm text-white/75">{{ place }}</p>

                <p class="mt-5 text-5xl font-semibold tracking-tight sm:text-6xl" data-test="temperature">
                    {{ format(weather.temperature, '°C') }}
                </p>
                <p class="mt-1 text-base font-medium" data-test="condition">{{ weather.condition }}</p>
            </div>

            <div class="flex items-center gap-4 sm:flex-col sm:items-end">
                <span class="flex size-20 items-center justify-center rounded-2xl bg-white/15 sm:size-24">
                    <AppIcon :name="icon" class="size-12 sm:size-14" stroke-width="1.5" />
                </span>
                <p class="text-xs text-white/75">
                    {{ weather.is_day ? 'Daytime' : 'Night-time' }}<template v-if="observedAt"> · as of {{ observedAt }} local</template>
                </p>
            </div>
        </div>

        <dl class="grid grid-cols-2 divide-slate-100 sm:grid-cols-4 sm:divide-x">
            <div v-for="item in details" :key="item.label" class="flex items-center gap-3 px-5 py-4">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                    <AppIcon :name="item.icon" class="size-5" />
                </span>
                <div class="min-w-0">
                    <dt class="text-xs text-slate-500">{{ item.label }}</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ item.value }}</dd>
                </div>
            </div>
        </dl>
    </article>
</template>
