<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCameraImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $device = $this->route('device');

        return $device !== null && ($this->user()?->can('update', $device) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image_quality' => ['required', 'string', 'in:hemat,standar,tajam,penuh'],
            'image_settings' => ['required', 'array'],
            'image_settings.brightness' => ['required', 'integer', 'between:-2,2'],
            'image_settings.contrast' => ['required', 'integer', 'between:-2,2'],
            'image_settings.saturation' => ['required', 'integer', 'between:-2,2'],
            'image_settings.sharpness' => ['required', 'integer', 'between:-2,2'],
            'image_settings.ae_level' => ['required', 'integer', 'between:-2,2'],
            'image_settings.wb_mode' => ['required', 'integer', 'between:0,4'],
            'image_settings.hmirror' => ['required', 'boolean'],
            'image_settings.vflip' => ['required', 'boolean'],
        ];
    }
}
