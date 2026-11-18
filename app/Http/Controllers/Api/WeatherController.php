<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CurrentWeatherRequest;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;

class WeatherController extends Controller
{
    // failures are WeatherLookupExceptions, which render their own JSON
    public function __invoke(CurrentWeatherRequest $request, WeatherService $weather): JsonResponse
    {
        return response()->json([
            'data' => $weather->currentForCity($request->city()),
        ]);
    }
}
