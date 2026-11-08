<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class InitRecordingRequest extends FormRequest
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
        return [
            'started_at' => ['required', 'date'],
            'mime' => ['nullable', 'string', 'max:100'],
            'bytes_total' => ['nullable', 'integer', 'min:1'],
            'chunk_size' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
