<?php

namespace App\Modules\Landing\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLiveRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['nullable', 'required_if_accepted:is_live', 'url:https', 'max:500'],
            'title' => ['nullable', 'string', 'max:120'],
            'is_live' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required_if_accepted' => 'Isi tautan live streaming sebelum menandai sedang live.',
            'url.url' => 'Tautan harus diawali https://.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'url' => 'tautan live streaming',
            'title' => 'judul',
        ];
    }
}
