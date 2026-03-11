<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shared validation contract used by export preview/download endpoints.
     */
    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', Rule::exists('sites', 'id')],
            'export_type' => ['nullable', 'string', Rule::in(['banner', 'text'])],

            // Optional filters (shared contract for current + future export logic).
            'network' => ['nullable', 'string', Rule::in(['flexoffers', 'awin', 'cj', 'impact'])],
            'advertiser_id' => ['nullable', 'integer', Rule::exists('advertisers', 'id')],
            'dimensions' => ['nullable', 'string', 'regex:/^\d+x\d+$/'],
            'search' => ['nullable', 'string', 'max:255'],
            'active_sizes_only' => ['nullable', 'boolean'],
        ];
    }
}
