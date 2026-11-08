<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StorePreviewRequest extends FormRequest
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

        return [
            'image' => ['required', 'file', 'mimes:jpeg,jpg', 'max:'.$max],
        ];
    }

    public function previewFile(): UploadedFile
    {
        return $this->file('image');
    }
}
