<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUserAction $registerUser): JsonResponse
    {
        $user = $registerUser->execute($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): UserResource
    {
        $request->authenticate();

        // fresh session id after login to rule out session fixation
        $request->session()->regenerate();

        return UserResource::make($request->user());
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function user(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
