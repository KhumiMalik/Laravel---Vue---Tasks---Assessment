<?php

namespace App\Http\Controllers\Api;

use App\Actions\Task\CreateTaskAction;
use App\Actions\Task\DeleteTaskAction;
use App\Actions\Task\ForceDeleteTaskAction;
use App\Actions\Task\RestoreTaskAction;
use App\Actions\Task\UpdateTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TaskController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly TaskService $tasks,
    ) {}

    // Policy runs as middleware so it's checked before the form request validates.
    // Otherwise poking at someone else's task id would return 422s instead of a 403.
    public static function middleware(): array
    {
        return [
            new Middleware('can:view,task', only: ['show']),
            new Middleware('can:update,task', only: ['update', 'toggleCompletion']),
            new Middleware('can:delete,task', only: ['destroy']),
            new Middleware('can:restore,task', only: ['restore']),
            new Middleware('can:forceDelete,task', only: ['forceDelete']),
        ];
    }

    public function index(ListTasksRequest $request): AnonymousResourceCollection
    {
        $tasks = $this->tasks->paginateForUser(
            $request->user(),
            $request->status(),
            $request->searchTerm(),
            $request->perPage(),
        );

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, CreateTaskAction $createTask): JsonResponse
    {
        $task = $createTask->execute($request->user(), $request->validated());

        return TaskResource::make($task)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task): TaskResource
    {
        return TaskResource::make($task);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $updateTask): TaskResource
    {
        return TaskResource::make($updateTask->execute($task, $request->validated()));
    }

    public function destroy(Task $task, DeleteTaskAction $deleteTask): Response
    {
        $deleteTask->execute($task);

        return response()->noContent();
    }

    public function toggleCompletion(Task $task): TaskResource
    {
        return TaskResource::make($this->tasks->toggleCompletion($task));
    }

    public function statistics(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->tasks->statisticsForUser($request->user()),
        ]);
    }

    public function trash(ListTasksRequest $request): AnonymousResourceCollection
    {
        $tasks = $this->tasks->paginateTrashForUser(
            $request->user(),
            $request->searchTerm(),
            $request->perPage(),
        );

        return TaskResource::collection($tasks);
    }

    public function restore(Task $task, RestoreTaskAction $restoreTask): TaskResource
    {
        return TaskResource::make($restoreTask->execute($task));
    }

    public function forceDelete(Task $task, ForceDeleteTaskAction $forceDeleteTask): Response
    {
        $forceDeleteTask->execute($task);

        return response()->noContent();
    }
}
