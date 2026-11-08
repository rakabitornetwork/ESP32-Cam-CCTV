<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChunkRecordingRequest extends FormRequest
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
        $max = (int) config('cctv.chunk_max_kb', 51200);

        return [
            'index' => ['required', 'integer', 'min:0', 'max:10000'],
            'chunk' => ['required', 'file', 'max:'.$max],
        ];
    }
}
