<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskActivityResource;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskActivityController extends Controller
{
    public function __invoke(Request $request, TaskService $tasks): AnonymousResourceCollection
    {
        return TaskActivityResource::collection($tasks->recentActivityForUser($request->user()));
    }
}
