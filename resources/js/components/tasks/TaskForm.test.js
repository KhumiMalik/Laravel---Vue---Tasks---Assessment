import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import TaskForm from './TaskForm.vue';

const existingTask = {
    id: 7,
    title: 'Write the README',
    description: 'Cover setup and the API',
    is_completed: false,
};

describe('TaskForm', () => {
    it('blocks submission and shows an error when the title is blank', async () => {
        const wrapper = mount(TaskForm);

        await wrapper.find('input[name="title"]').setValue('   ');
        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')).toBeUndefined();
        expect(wrapper.text()).toContain('The title field is required.');
        expect(wrapper.find('input[name="title"]').attributes('aria-invalid')).toBe('true');
    });

    it('emits a trimmed payload and sends an empty description as null', async () => {
        const wrapper = mount(TaskForm);

        await wrapper.find('input[name="title"]').setValue('  Buy milk  ');
        await wrapper.find('textarea[name="description"]').setValue('   ');
        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')[0][0]).toEqual({ title: 'Buy milk', description: null });
    });

    it('leaves is_completed out when creating', async () => {
        const wrapper = mount(TaskForm);

        expect(wrapper.find('input[name="is_completed"]').exists()).toBe(false);
        expect(wrapper.find('button[type="submit"]').text()).toBe('Create task');
    });

    it('prefills the fields and includes is_completed when editing', async () => {
        const wrapper = mount(TaskForm, { props: { task: existingTask } });

        expect(wrapper.find('input[name="title"]').element.value).toBe('Write the README');
        expect(wrapper.find('textarea[name="description"]').element.value).toBe('Cover setup and the API');
        expect(wrapper.find('button[type="submit"]').text()).toBe('Save changes');

        await wrapper.find('input[name="is_completed"]').setValue(true);
        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')[0][0]).toEqual({
            title: 'Write the README',
            description: 'Cover setup and the API',
            is_completed: true,
        });
    });

    it('shows validation errors returned by the API', () => {
        const wrapper = mount(TaskForm, {
            props: { errors: { title: ['The title field must not be greater than 255 characters.'] } },
        });

        expect(wrapper.text()).toContain('The title field must not be greater than 255 characters.');
    });

    it('disables the fields while saving', () => {
        const wrapper = mount(TaskForm, { props: { processing: true } });

        expect(wrapper.find('input[name="title"]').attributes('disabled')).toBeDefined();
        expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined();
    });

    it('emits cancel', async () => {
        const wrapper = mount(TaskForm);

        await wrapper.findAll('button').find((button) => button.text() === 'Cancel').trigger('click');

        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });
});
