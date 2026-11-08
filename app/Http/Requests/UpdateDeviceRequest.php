<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $device = $this->route('device');

        return $device !== null && ($this->user()?->can('update', $device) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'location' => $this->filled('location') ? $this->input('location') : null,
            'latitude' => $this->filled('latitude') ? $this->input('latitude') : null,
            'longitude' => $this->filled('longitude') ? $this->input('longitude') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'image_quality' => ['sometimes', 'string', 'in:hemat,standar,tajam'],
            'image_settings' => ['sometimes', 'array'],
            'image_settings.brightness' => ['required_with:image_settings', 'integer', 'between:-2,2'],
            'image_settings.contrast' => ['required_with:image_settings', 'integer', 'between:-2,2'],
            'image_settings.saturation' => ['required_with:image_settings', 'integer', 'between:-2,2'],
            'image_settings.sharpness' => ['required_with:image_settings', 'integer', 'between:-2,2'],
            'image_settings.ae_level' => ['required_with:image_settings', 'integer', 'between:-2,2'],
            'image_settings.wb_mode' => ['required_with:image_settings', 'integer', 'between:0,4'],
            'image_settings.hmirror' => ['required_with:image_settings', 'boolean'],
            'image_settings.vflip' => ['required_with:image_settings', 'boolean'],
        ];
    }
}
