<?php

namespace App\Modules\Events\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveEventRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $image = config('modules.events.image');

        return [
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:160'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'registration_url' => ['nullable', 'url:https,http', 'max:255'],
            'is_published' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:'.implode(',', $image['mimes']), 'max:'.$image['max_kb']],
            'remove_image' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'summary' => 'ringkasan',
            'body' => 'deskripsi',
            'location' => 'lokasi',
            'starts_at' => 'waktu mulai',
            'ends_at' => 'waktu selesai',
            'registration_url' => 'tautan pendaftaran',
            'image' => 'gambar',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.after' => 'Waktu selesai harus setelah waktu mulai.',
        ];
    }
}
