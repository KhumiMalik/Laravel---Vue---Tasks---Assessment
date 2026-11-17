import { computed, reactive, ref, watch } from 'vue';
import taskService from '@/services/taskService';
import { debounce } from '@/utils/debounce';

const EMPTY_STATS = { total: 0, completed: 0, pending: 0, trashed: 0 };

export function useTasks() {
    const tasks = ref([]);
    const meta = ref(null);
    const statistics = ref({ ...EMPTY_STATS });
    const activities = ref([]);
    const filters = reactive({ search: '', status: 'all', page: 1 });

    const loading = ref(false);
    const loaded = ref(false);
    const error = ref(null);
    const busyIds = ref(new Set());

    const hasActiveFilters = computed(() => filters.search.trim() !== '' || filters.status !== 'all');

    // Filtering happens server-side (the list is paginated), so the per-tab counts
    // come from the statistics endpoint rather than counting the current page.
    const statusCounts = computed(() => ({
        all: statistics.value.total,
        pending: statistics.value.pending,
        completed: statistics.value.completed,
    }));

    let latestRequest = 0;

    async function fetchTasks() {
        const requestId = ++latestRequest;
        loading.value = true;
        error.value = null;

        try {
            const params = { page: filters.page, status: filters.status };
            const search = filters.search.trim();

            if (search) {
                params.search = search;
            }

            const response = await taskService.list(params);

            // typing fast fires several searches; ignore any answer that isn't the latest
            if (requestId !== latestRequest) {
                return;
            }

            // deleting the last task on the last page leaves us one page past the end
            if (response.data.length === 0 && response.meta.current_page > response.meta.last_page) {
                filters.page = response.meta.last_page;
                return;
            }

            tasks.value = response.data;
            meta.value = response.meta;
            loaded.value = true;
        } catch (e) {
            if (requestId === latestRequest) {
                error.value = e.message;
            }
        } finally {
            if (requestId === latestRequest) {
                loading.value = false;
            }
        }
    }

    async function fetchStatistics() {
        try {
            statistics.value = await taskService.statistics();
        } catch {
            // stats are secondary; the list shows its own error state
        }
    }

    async function fetchActivity() {
        try {
            activities.value = await taskService.activities();
        } catch {
            // same as above, the panel just keeps what it had
        }
    }

    // Activity rows are written by a queued listener, so give the worker a moment.
    let activityTimer;
    function refreshActivitySoon() {
        clearTimeout(activityTimer);
        activityTimer = setTimeout(fetchActivity, 1500);
    }

    function load() {
        return Promise.all([fetchTasks(), fetchStatistics(), fetchActivity()]);
    }

    function refresh() {
        return Promise.all([fetchTasks(), fetchStatistics()]);
    }

    function resetPageAndFetch() {
        if (filters.page === 1) {
            fetchTasks();
        } else {
            filters.page = 1; // the page watcher does the fetch
        }
    }

    watch(() => filters.status, resetPageAndFetch);
    watch(() => filters.search, debounce(resetPageAndFetch, 300));
    watch(() => filters.page, fetchTasks);

    function clearFilters() {
        filters.search = '';
        filters.status = 'all';
    }

    function setBusy(id, busy) {
        const next = new Set(busyIds.value);
        busy ? next.add(id) : next.delete(id);
        busyIds.value = next;
    }

    function replaceLocal(updated) {
        tasks.value = tasks.value.map((task) => (task.id === updated.id ? updated : task));
    }

    async function createTask(payload) {
        const task = await taskService.create(payload);

        // newest first, so the new task lands at the top of page one
        resetPageAndFetch();
        fetchStatistics();

        return task;
    }

    async function updateTask(id, payload) {
        const before = tasks.value.find((task) => task.id === id);
        const updated = await taskService.update(id, payload);

        replaceLocal(updated);

        // an edited title or status may no longer match what's being shown
        if (hasActiveFilters.value) {
            fetchTasks();
        }

        fetchStatistics();

        if (updated.is_completed && before && !before.is_completed) {
            refreshActivitySoon();
        }

        return updated;
    }

    async function toggleTask(task) {
        // a click while the previous one is still in flight is a no-op
        if (busyIds.value.has(task.id)) {
            return null;
        }

        const original = { ...task };

        setBusy(task.id, true);
        replaceLocal({ ...task, is_completed: !task.is_completed });

        try {
            const updated = await taskService.toggleCompletion(task.id);

            replaceLocal(updated);

            if (filters.status !== 'all') {
                fetchTasks(); // it just left the current filter
            }

            fetchStatistics();

            if (updated.is_completed) {
                refreshActivitySoon();
            }

            return updated;
        } catch (e) {
            replaceLocal(original);
            throw e;
        } finally {
            setBusy(task.id, false);
        }
    }

    async function deleteTask(id) {
        await taskService.remove(id);
        await refresh();
    }

    async function restoreTask(id) {
        const task = await taskService.restore(id);
        await refresh();
        return task;
    }

    return {
        tasks,
        meta,
        statistics,
        activities,
        filters,
        loading,
        loaded,
        error,
        busyIds,
        hasActiveFilters,
        statusCounts,
        load,
        fetchTasks,
        clearFilters,
        createTask,
        updateTask,
        toggleTask,
        deleteTask,
        restoreTask,
    };
}
