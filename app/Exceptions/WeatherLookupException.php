<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Everything that can go wrong between "user typed a city" and "we have weather",
 * already translated into a status code and a message that's safe to show.
 * The upstream detail goes to the log, never into the response.
 */
class WeatherLookupException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status)
    {
        parent::__construct($message);
    }

    public static function cityNotFound(string $city): self
    {
        return new self("We couldn't find a city called \"{$city}\". Check the spelling and try again.", Response::HTTP_NOT_FOUND);
    }

    public static function unavailable(): self
    {
        return new self('The weather service is unavailable right now. Please try again in a moment.', Response::HTTP_SERVICE_UNAVAILABLE);
    }

    public static function timedOut(): self
    {
        return new self('The weather service took too long to respond. Please try again.', Response::HTTP_GATEWAY_TIMEOUT);
    }

    public static function badResponse(): self
    {
        return new self('The weather service sent back data we couldn\'t read. Please try again later.', Response::HTTP_BAD_GATEWAY);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->status);
    }

    // true = "handled", so Laravel doesn't log a stack trace for an expected failure.
    // WeatherService already logged the upstream context where it matters.
    public function report(): bool
    {
        return true;
    }
}
