import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import TaskFilters from './TaskFilters.vue';

// mirrors how a parent binds v-model:search / v-model:status
function mountFilters(props = {}) {
    const wrapper = mount(TaskFilters, {
        props: {
            search: '',
            status: 'all',
            'onUpdate:search': (value) => wrapper.setProps({ search: value }),
            'onUpdate:status': (value) => wrapper.setProps({ status: value }),
            ...props,
        },
    });

    return wrapper;
}

describe('TaskFilters', () => {
    it('updates the search model as the user types', async () => {
        const wrapper = mountFilters();

        await wrapper.find('input[type="search"]').setValue('invoice');

        expect(wrapper.emitted('update:search').at(-1)).toEqual(['invoice']);
    });

    it('updates the status model when a status is picked', async () => {
        const wrapper = mountFilters();

        await wrapper.find('input[value="completed"]').setValue(true);

        expect(wrapper.emitted('update:status').at(-1)).toEqual(['completed']);
    });

    it('checks the radio matching the current status', () => {
        const wrapper = mountFilters({ status: 'pending' });

        expect(wrapper.find('input[value="pending"]').element.checked).toBe(true);
        expect(wrapper.find('input[value="all"]').element.checked).toBe(false);
    });

    it('shows a clear button only while there is a search term', async () => {
        const wrapper = mountFilters();

        expect(wrapper.find('button[aria-label="Clear search"]').exists()).toBe(false);

        await wrapper.setProps({ search: 'report' });
        await wrapper.find('button[aria-label="Clear search"]').trigger('click');

        expect(wrapper.emitted('update:search').at(-1)).toEqual(['']);
    });

    it('shows the count next to each status', () => {
        const wrapper = mountFilters({ counts: { all: 12, pending: 7, completed: 5 } });
        const labels = wrapper.findAll('label').map((label) => label.text().replace(/\s+/g, ' '));

        expect(labels).toEqual(expect.arrayContaining(['All 12', 'Pending 7', 'Completed 5']));
    });
});
