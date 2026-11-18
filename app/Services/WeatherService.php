<?php

namespace App\Services;

use App\Exceptions\WeatherLookupException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Current weather for a city name via Open-Meteo: geocode the name, then ask the
 * forecast API for the "current" block at those coordinates. Lives in a service
 * rather than an action because it's two dependent external calls plus mapping.
 *
 * @see https://open-meteo.com/en/docs
 * @see https://open-meteo.com/en/docs/geocoding-api
 */
class WeatherService
{
    private const CURRENT_FIELDS = [
        'temperature_2m',
        'apparent_temperature',
        'relative_humidity_2m',
        'weather_code',
        'wind_speed_10m',
        'precipitation',
        'is_day',
    ];

    // WMO interpretation codes, as listed at the bottom of the Open-Meteo forecast docs
    private const CONDITIONS = [
        0 => 'Clear sky',
        1 => 'Mainly clear',
        2 => 'Partly cloudy',
        3 => 'Overcast',
        45 => 'Fog',
        48 => 'Depositing rime fog',
        51 => 'Light drizzle',
        53 => 'Moderate drizzle',
        55 => 'Dense drizzle',
        56 => 'Light freezing drizzle',
        57 => 'Dense freezing drizzle',
        61 => 'Slight rain',
        63 => 'Moderate rain',
        65 => 'Heavy rain',
        66 => 'Light freezing rain',
        67 => 'Heavy freezing rain',
        71 => 'Slight snowfall',
        73 => 'Moderate snowfall',
        75 => 'Heavy snowfall',
        77 => 'Snow grains',
        80 => 'Slight rain showers',
        81 => 'Moderate rain showers',
        82 => 'Violent rain showers',
        85 => 'Slight snow showers',
        86 => 'Heavy snow showers',
        95 => 'Thunderstorm',
        96 => 'Thunderstorm with slight hail',
        99 => 'Thunderstorm with heavy hail',
    ];

    /**
     * @return array{city: string, region: ?string, country: ?string, temperature: float, feels_like: ?float,
     *     humidity: ?int, condition: string, weather_code: int, wind_speed: ?float, precipitation: ?float,
     *     is_day: bool, observed_at: ?string}
     *
     * @throws WeatherLookupException
     */
    public function currentForCity(string $city): array
    {
        $place = $this->geocode($city);
        $current = $this->currentAt($place['latitude'], $place['longitude']);

        $code = (int) $current['weather_code'];

        return [
            'city' => $place['name'],
            'region' => $this->stringOrNull($place['admin1'] ?? null),
            'country' => $this->stringOrNull($place['country'] ?? null),
            'temperature' => (float) $current['temperature_2m'],
            'feels_like' => $this->numberOrNull($current['apparent_temperature'] ?? null),
            'humidity' => isset($current['relative_humidity_2m']) && is_numeric($current['relative_humidity_2m'])
                ? (int) round($current['relative_humidity_2m'])
                : null,
            'condition' => self::CONDITIONS[$code] ?? 'Unknown',
            'weather_code' => $code,
            'wind_speed' => $this->numberOrNull($current['wind_speed_10m'] ?? null),
            'precipitation' => $this->numberOrNull($current['precipitation'] ?? null),
            // missing is_day shouldn't fail the whole lookup; daytime icon is the safer default
            'is_day' => (int) ($current['is_day'] ?? 1) === 1,
            // local time at the location, ISO 8601 without offset (timezone=auto)
            'observed_at' => $this->stringOrNull($current['time'] ?? null),
        ];
    }

    /**
     * @return array{name: string, latitude: float, longitude: float, admin1?: mixed, country?: mixed}
     */
    private function geocode(string $city): array
    {
        $response = $this->send('geocoding', config('services.open_meteo.geocoding_url'), [
            'name' => $city,
            'count' => 1, // results come back ranked by relevance/population, so take the top match
            'language' => 'en',
            'format' => 'json',
        ]);

        $payload = $response->json();

        if (! is_array($payload)) {
            $this->logBadPayload('geocoding', $response);

            throw WeatherLookupException::badResponse();
        }

        // Open-Meteo omits "results" entirely (rather than sending []) when nothing matches
        $place = $payload['results'][0] ?? null;

        if ($place === null) {
            throw WeatherLookupException::cityNotFound($city);
        }

        if (! is_array($place)
            || ! is_string($place['name'] ?? null)
            || ! is_numeric($place['latitude'] ?? null)
            || ! is_numeric($place['longitude'] ?? null)) {
            $this->logBadPayload('geocoding', $response);

            throw WeatherLookupException::badResponse();
        }

        $place['latitude'] = (float) $place['latitude'];
        $place['longitude'] = (float) $place['longitude'];

        return $place;
    }

    /**
     * @return array<string, mixed>
     */
    private function currentAt(float $latitude, float $longitude): array
    {
        $response = $this->send('forecast', config('services.open_meteo.forecast_url'), [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => implode(',', self::CURRENT_FIELDS),
            // these are the API defaults today; pinned so a default change can't silently switch units
            'temperature_unit' => 'celsius',
            'wind_speed_unit' => 'kmh',
            'precipitation_unit' => 'mm',
            'timezone' => 'auto',
        ]);

        $current = $response->json('current');

        // temperature and code are the minimum we can show; the rest degrade to null
        if (! is_array($current)
            || ! is_numeric($current['temperature_2m'] ?? null)
            || ! is_numeric($current['weather_code'] ?? null)) {
            $this->logBadPayload('forecast', $response);

            throw WeatherLookupException::badResponse();
        }

        return $current;
    }

    private function send(string $api, string $url, array $query): Response
    {
        try {
            $response = $this->http()->get($url, $query);
        } catch (ConnectionException $e) {
            // covers both timeouts and DNS/connection failures; guzzle doesn't give us a clean way to tell them apart
            Log::warning("Open-Meteo {$api} request did not complete", ['error' => $e->getMessage()]);

            throw WeatherLookupException::timedOut();
        }

        if ($response->failed()) {
            Log::warning("Open-Meteo {$api} request failed", [
                'status' => $response->status(),
                'reason' => $response->json('reason'), // Open-Meteo puts its error text here
            ]);

            throw WeatherLookupException::unavailable();
        }

        return $response;
    }

    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->connectTimeout(config('services.open_meteo.connect_timeout'))
            ->timeout(config('services.open_meteo.timeout'));
    }

    private function logBadPayload(string $api, Response $response): void
    {
        Log::warning("Open-Meteo {$api} returned an unexpected payload", [
            'body' => mb_substr($response->body(), 0, 500),
        ]);
    }

    private function numberOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
