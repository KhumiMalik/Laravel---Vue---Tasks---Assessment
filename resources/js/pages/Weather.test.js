import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import weatherService from '@/services/weatherService';
import Weather from './Weather.vue';

vi.mock('@/services/weatherService', () => ({
    default: { current: vi.fn() },
}));

const LAHORE = {
    city: 'Lahore',
    region: 'Punjab',
    country: 'Pakistan',
    temperature: 23.7,
    feels_like: 23.6,
    humidity: 46,
    condition: 'Partly cloudy',
    weather_code: 2,
    wind_speed: 4.7,
    precipitation: 0,
    is_day: true,
    observed_at: '2026-11-17T12:15',
};

// shaped like the errors api.js rejects with
function apiError(status, message, errors = {}) {
    return Object.assign(new Error(message), { status, errors });
}

// a promise the test resolves/rejects by hand, to look at the in-flight state
function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((res, rej) => {
        resolve = res;
        reject = rej;
    });
    return { promise, resolve, reject };
}

function mountPage() {
    // the real layout pulls in the sidebar, auth and router; the page itself doesn't need them
    return mount(Weather, {
        global: {
            stubs: { AppLayout: { props: ['title'], template: '<div><h1>{{ title }}</h1><slot /></div>' } },
        },
    });
}

async function searchFor(wrapper, city) {
    await wrapper.find('input[name="city"]').setValue(city);
    await wrapper.find('form').trigger('submit');
}

describe('Weather page', () => {
    beforeEach(() => {
        weatherService.current.mockReset();
    });

    it('renders the heading, search field and an empty state', () => {
        const wrapper = mountPage();

        expect(wrapper.find('h1').text()).toBe('Weather');
        expect(wrapper.find('input[name="city"]').exists()).toBe(true);
        expect(wrapper.find('button[type="submit"]').text()).toContain('Search');
        expect(wrapper.text()).toContain('Check the weather anywhere');
    });

    it('does not call the API for an empty search', async () => {
        const wrapper = mountPage();

        await searchFor(wrapper, '   ');

        expect(weatherService.current).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain('Please enter a city name.');
    });

    it('shows a loading state and blocks a second submit while the request is in flight', async () => {
        const pending = deferred();
        weatherService.current.mockReturnValue(pending.promise);
        const wrapper = mountPage();

        await searchFor(wrapper, '  Lahore ');

        expect(weatherService.current).toHaveBeenCalledWith('Lahore');
        expect(wrapper.text()).toContain('Checking the weather in Lahore');
        expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined();

        // Enter again while loading
        await wrapper.find('form').trigger('submit');
        expect(weatherService.current).toHaveBeenCalledTimes(1);

        pending.resolve(LAHORE);
        await flushPromises();

        expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeUndefined();
    });

    it('shows the weather returned by the API', async () => {
        weatherService.current.mockResolvedValue(LAHORE);
        const wrapper = mountPage();

        await searchFor(wrapper, 'Lahore');
        await flushPromises();

        const text = wrapper.text();
        expect(text).toContain('Lahore');
        expect(text).toContain('Punjab, Pakistan');
        expect(wrapper.find('[data-test="temperature"]').text()).toBe('23.7°C');
        expect(wrapper.find('[data-test="condition"]').text()).toBe('Partly cloudy');
        expect(text).toContain('23.6°C'); // feels like
        expect(text).toContain('46%');
        expect(text).toContain('4.7 km/h');
        expect(text).toContain('0 mm');
    });

    it('clears the previous result as soon as a new search starts', async () => {
        weatherService.current.mockResolvedValueOnce(LAHORE);
        const wrapper = mountPage();

        await searchFor(wrapper, 'Lahore');
        await flushPromises();

        const pending = deferred();
        weatherService.current.mockReturnValueOnce(pending.promise);
        await searchFor(wrapper, 'Karachi');

        expect(wrapper.find('[data-test="temperature"]').exists()).toBe(false);
        expect(wrapper.text()).toContain('Checking the weather in Karachi');

        pending.reject(apiError(404, 'nope'));
        await flushPromises();
    });

    it('shows the server message when the city is not found', async () => {
        weatherService.current.mockRejectedValue(
            apiError(404, 'We couldn\'t find a city called "Atlantis". Check the spelling and try again.'),
        );
        const wrapper = mountPage();

        await searchFor(wrapper, 'Atlantis');
        await flushPromises();

        expect(wrapper.find('[role="alert"]').text()).toContain('City not found');
        expect(wrapper.text()).toContain('We couldn\'t find a city called "Atlantis"');
        expect(wrapper.text()).not.toContain('Try again'); // retrying the same typo won't help
    });

    it('explains an outage and lets the user retry the same city', async () => {
        weatherService.current.mockRejectedValueOnce(apiError(503, 'Something went wrong on our side. Please try again.'));
        const wrapper = mountPage();

        await searchFor(wrapper, 'Lahore');
        await flushPromises();

        expect(wrapper.find('[role="alert"]').text()).toContain('Weather service unavailable');

        weatherService.current.mockResolvedValueOnce(LAHORE);
        await wrapper.findAll('button').find((b) => b.text() === 'Try again').trigger('click');
        await flushPromises();

        expect(weatherService.current).toHaveBeenLastCalledWith('Lahore');
        expect(wrapper.find('[data-test="temperature"]').exists()).toBe(true);
    });

    it('tells a timeout apart from an outage', async () => {
        weatherService.current.mockRejectedValue(apiError(504, 'Something went wrong on our side. Please try again.'));
        const wrapper = mountPage();

        await searchFor(wrapper, 'Lahore');
        await flushPromises();

        expect(wrapper.find('[role="alert"]').text()).toContain('Weather service timed out');
    });

    it('puts server-side validation errors on the field', async () => {
        weatherService.current.mockRejectedValue(
            apiError(422, 'Invalid', { city: ['Enter just the city name, e.g. Lahore.'] }),
        );
        const wrapper = mountPage();

        await searchFor(wrapper, 'Lahore, Pakistan');
        await flushPromises();

        expect(wrapper.text()).toContain('Enter just the city name, e.g. Lahore.');
        expect(wrapper.find('[role="alert"]').exists()).toBe(false);
    });
});
