<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CurrentWeatherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Unicode letters/marks so "São Paulo", "Zürich" and "N'Djamena" pass.
            // No commas: Open-Meteo matches on the place name only, so "Lahore, Pakistan" finds nothing.
            'city' => ['required', 'string', 'min:2', 'max:100', "regex:/^[\pL\pM][\pL\pM\s'’.\-]*$/u"],
        ];
    }

    public function messages(): array
    {
        return [
            'city.required' => 'Please enter a city name.',
            'city.min' => 'City names need at least 2 characters.',
            'city.regex' => 'Enter just the city name, e.g. Lahore. Letters, spaces, hyphens and apostrophes only.',
        ];
    }

    public function city(): string
    {
        // collapse "New   York" so it reads normally in the not-found message
        return preg_replace('/\s+/u', ' ', $this->validated('city'));
    }
}
