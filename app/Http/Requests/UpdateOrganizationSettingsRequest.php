<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->user()?->currentOrganization();

        return $organization !== null
            && $this->user()->canManageOrganization($organization->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = $this->user()?->currentOrganization()?->subscription?->plan?->retain_days ?? 7;

        return [
            'retain_days' => ['required', 'integer', 'min:1', 'max:'.$max],
        ];
    }
}
