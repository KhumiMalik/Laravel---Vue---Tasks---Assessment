<?php

namespace Tests\Feature\Weather;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    private const GEOCODING = 'geocoding-api.open-meteo.com/*';

    private const FORECAST = 'api.open-meteo.com/*';

    protected function setUp(): void
    {
        parent::setUp();

        // nothing in this file may reach the real Open-Meteo
        Http::preventStrayRequests();

        $this->actingAs(User::factory()->make(['id' => 1]));
    }

    public function test_it_returns_current_weather_for_a_city(): void
    {
        Http::fake([
            self::GEOCODING => Http::response($this->lahore()),
            self::FORECAST => Http::response($this->forecast()),
        ]);

        $this->getJson('/api/weather?city=Lahore')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'city' => 'Lahore',
                    'region' => 'Punjab',
                    'country' => 'Pakistan',
                    'temperature' => 23.7,
                    'feels_like' => 23.6,
                    'humidity' => 46,
                    'condition' => 'Partly cloudy',
                    'weather_code' => 2,
                    'wind_speed' => 4.7,
                    'precipitation' => 0, // JSON has no int/float distinction; 0.0 encodes as 0
                    'is_day' => true,
                    'observed_at' => '2026-11-17T12:15',
                ],
            ]);

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://geocoding-api.open-meteo.com/v1/search')
            && $request['name'] === 'Lahore'
            && $request['count'] === 1);

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.open-meteo.com/v1/forecast')
            && $request['latitude'] === 31.558
            && $request['longitude'] === 74.35071
            && $request['temperature_unit'] === 'celsius'
            && $request['wind_speed_unit'] === 'kmh'
            && str_contains($request['current'], 'apparent_temperature'));
    }

    public function test_night_time_and_unknown_codes_are_mapped(): void
    {
        Http::fake([
            self::GEOCODING => Http::response($this->lahore()),
            self::FORECAST => Http::response($this->forecast(['is_day' => 0, 'weather_code' => 42])),
        ]);

        $this->getJson('/api/weather?city=Lahore')
            ->assertOk()
            ->assertJsonPath('data.is_day', false)
            ->assertJsonPath('data.condition', 'Unknown')
            ->assertJsonPath('data.weather_code', 42);
    }

    public function test_optional_fields_missing_upstream_come_back_as_null(): void
    {
        Http::fake([
            self::GEOCODING => Http::response($this->lahore()),
            self::FORECAST => Http::response($this->forecast([
                'apparent_temperature' => null,
                'relative_humidity_2m' => null,
                'wind_speed_10m' => null,
            ])),
        ]);

        $this->getJson('/api/weather?city=Lahore')
            ->assertOk()
            ->assertJsonPath('data.feels_like', null)
            ->assertJsonPath('data.humidity', null)
            ->assertJsonPath('data.wind_speed', null)
            ->assertJsonPath('data.temperature', 23.7);
    }

    public function test_city_is_required(): void
    {
        Http::fake();

        $this->getJson('/api/weather?city=')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city' => 'Please enter a city name.']);

        $this->getJson('/api/weather')->assertJsonValidationErrors('city');
        $this->getJson('/api/weather?city=%20%20%20')->assertJsonValidationErrors('city');

        Http::assertNothingSent();
    }

    public function test_city_must_look_like_a_place_name(): void
    {
        Http::fake();

        foreach (['L', 'Lahore, Pakistan', '12345', '<script>', str_repeat('a', 101)] as $city) {
            $this->getJson('/api/weather?'.http_build_query(['city' => $city]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('city');
        }

        Http::assertNothingSent();
    }

    public function test_accented_and_punctuated_names_are_accepted(): void
    {
        Http::fake([
            self::GEOCODING => Http::response($this->lahore()),
            self::FORECAST => Http::response($this->forecast()),
        ]);

        foreach (['São Paulo', "N'Djamena", 'Saint-Étienne', 'St. Louis'] as $city) {
            $this->getJson('/api/weather?'.http_build_query(['city' => $city]))->assertOk();
        }
    }

    public function test_unknown_city_returns_404_without_calling_the_forecast_api(): void
    {
        // what Open-Meteo really sends for no match: no "results" key at all
        Http::fake([self::GEOCODING => Http::response(['generationtime_ms' => 0.13])]);

        $this->getJson('/api/weather?city=Atlantis')
            ->assertNotFound()
            ->assertExactJson(['message' => 'We couldn\'t find a city called "Atlantis". Check the spelling and try again.']);

        Http::assertSentCount(1);
    }

    public function test_geocoding_outage_returns_503(): void
    {
        Http::fake([self::GEOCODING => Http::response('<html>Bad Gateway</html>', 502)]);

        $this->getJson('/api/weather?city=Lahore')
            ->assertServiceUnavailable()
            ->assertExactJson(['message' => 'The weather service is unavailable right now. Please try again in a moment.']);

        Http::assertSentCount(1);
    }

    public function test_forecast_outage_returns_503_without_leaking_the_upstream_error(): void
    {
        Http::fake([
            self::GEOCODING => Http::response($this->lahore()),
            self::FORECAST => Http::response(['error' => true, 'reason' => 'Internal model error at node 7'], 500),
        ]);

        $this->getJson('/api/weather?city=Lahore')
            ->assertServiceUnavailable()
            ->assertDontSee('node 7');
    }

    public function test_timeout_returns_504(): void
    {
        Http::fake([
            self::GEOCODING => Http::response($this->lahore()),
            self::FORECAST => fn () => throw new ConnectionException('cURL error 28: Operation timed out after 5001 milliseconds'),
        ]);

        $this->getJson('/api/weather?city=Lahore')
            ->assertStatus(504)
            ->assertExactJson(['message' => 'The weather service took too long to respond. Please try again.'])
            ->assertDontSee('cURL');
    }

    public function test_unexpected_geocoding_payload_returns_502(): void
    {
        Http::fake([
            self::GEOCODING => Http::response(['results' => [['name' => 'Lahore']]]), // no coordinates
        ]);

        $this->getJson('/api/weather?city=Lahore')
            ->assertStatus(502)
            ->assertJsonPath('message', 'The weather service sent back data we couldn\'t read. Please try again later.');

        Http::assertSentCount(1);
    }

    public function test_non_json_geocoding_body_returns_502(): void
    {
        Http::fake([self::GEOCODING => Http::response('not json', 200)]);

        $this->getJson('/api/weather?city=Lahore')->assertStatus(502);
    }

    public function test_incomplete_forecast_payload_returns_502(): void
    {
        Http::fake([
            self::GEOCODING => Http::response($this->lahore()),
            self::FORECAST => Http::response($this->forecast(['temperature_2m' => null])),
        ]);

        $this->getJson('/api/weather?city=Lahore')->assertStatus(502);
    }

    public function test_forecast_without_current_block_returns_502(): void
    {
        Http::fake([
            self::GEOCODING => Http::response($this->lahore()),
            self::FORECAST => Http::response(['latitude' => 31.6, 'longitude' => 74.4]),
        ]);

        $this->getJson('/api/weather?city=Lahore')->assertStatus(502);
    }

    public function test_guests_cannot_look_up_weather(): void
    {
        Http::fake();

        $this->app['auth']->forgetGuards();

        $this->getJson('/api/weather?city=Lahore')->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_lookups_are_rate_limited_per_user(): void
    {
        Http::fake([
            self::GEOCODING => Http::response($this->lahore()),
            self::FORECAST => Http::response($this->forecast()),
        ]);

        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/weather?city=Lahore')->assertOk();
        }

        $this->getJson('/api/weather?city=Lahore')->assertTooManyRequests();
    }

    private function lahore(): array
    {
        return [
            'results' => [[
                'id' => 1172451,
                'name' => 'Lahore',
                'latitude' => 31.558,
                'longitude' => 74.35071,
                'country_code' => 'PK',
                'timezone' => 'Asia/Karachi',
                'population' => 13004135,
                'country' => 'Pakistan',
                'admin1' => 'Punjab',
            ]],
            'generationtime_ms' => 0.22,
        ];
    }

    private function forecast(array $current = []): array
    {
        return [
            'latitude' => 31.599297,
            'longitude' => 74.371254,
            'timezone' => 'Asia/Karachi',
            'current_units' => ['temperature_2m' => '°C', 'wind_speed_10m' => 'km/h'],
            'current' => array_merge([
                'time' => '2026-11-17T12:15',
                'interval' => 900,
                'temperature_2m' => 23.7,
                'apparent_temperature' => 23.6,
                'relative_humidity_2m' => 46,
                'weather_code' => 2,
                'wind_speed_10m' => 4.7,
                'precipitation' => 0.0,
                'is_day' => 1,
            ], $current),
        ];
    }
}
