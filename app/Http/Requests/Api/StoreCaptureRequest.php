<?php

namespace App\Http\Requests\Api;

use App\Enums\CaptureTrigger;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreCaptureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('device') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = (int) config('cctv.max_upload_kb', 2048);
        $field = $this->hasFile('photo') && ! $this->hasFile('image') ? 'photo' : 'image';

        return [
            $field => ['required', 'file', 'mimes:jpeg,jpg', 'max:'.$max],
            'captured_at' => ['nullable', 'date'],
            'trigger' => ['nullable', Rule::enum(CaptureTrigger::class)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    public function captureFile(): UploadedFile
    {
        return $this->file('image') ?? $this->file('photo');
    }
}
