<?php

namespace App\Modules\Devotionals\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveDevotionalRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'scripture_ref' => ['required', 'string', 'max:100'],
            'scripture_text' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string', 'max:20000'],
            'publish_date' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'scripture_ref' => 'ayat rujukan',
            'scripture_text' => 'isi ayat',
            'body' => 'isi renungan',
            'publish_date' => 'tanggal terbit',
        ];
    }
}
