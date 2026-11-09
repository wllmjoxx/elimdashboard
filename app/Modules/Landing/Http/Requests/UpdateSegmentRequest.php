<?php

namespace App\Modules\Landing\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSegmentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $image = config('modules.landing.segment_image');

        return [
            'eyebrow' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:120'],
            'text' => ['nullable', 'string', 'max:600'],
            'button_label' => ['nullable', 'required_with:button_href', 'string', 'max:40'],
            // Internal path ("/jadwal") or full web address.
            'button_href' => ['nullable', 'required_with:button_label', 'string', 'max:255', 'regex:#^(/|https?://)#'],
            'align' => ['required', Rule::in(['left', 'center', 'right'])],
            'is_active' => ['boolean'],
            'remove_image' => ['boolean'],
            'image' => [
                'nullable',
                'image',
                'mimes:'.implode(',', $image['mimes']),
                'max:'.$image['max_kb'],
                Rule::dimensions()
                    ->minWidth($image['min_width'])
                    ->minHeight($image['min_height'])
                    ->maxWidth($image['max_width'])
                    ->maxHeight($image['max_height']),
            ],
        ];
    }

    /**
     * Segments are wide banners: refuse images narrower than the minimum ratio.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $file = $this->file('image');

                if (! $file instanceof UploadedFile || $validator->errors()->has('image')) {
                    return;
                }

                $size = @getimagesize($file->getRealPath());
                $minRatio = (float) config('modules.landing.segment_image.min_ratio');

                if ($size !== false && $size[1] > 0 && $minRatio > $size[0] / $size[1]) {
                    $validator->errors()->add('image', 'Gambar harus lanskap dengan rasio minimal 3:2 (lebar ÷ tinggi ≥ '.$minRatio.').');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'eyebrow' => 'label kecil',
            'title' => 'judul',
            'text' => 'teks',
            'button_label' => 'label tombol',
            'button_href' => 'tautan tombol',
            'align' => 'posisi teks',
            'image' => 'gambar latar',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $image = config('modules.landing.segment_image');

        return [
            'button_href.regex' => 'Tautan tombol harus diawali "/" (halaman situs ini) atau "https://".',
            'image.dimensions' => sprintf(
                'Resolusi gambar minimal %d × %d px dan maksimal %d × %d px.',
                $image['min_width'],
                $image['min_height'],
                $image['max_width'],
                $image['max_height'],
            ),
            'image.max' => sprintf('Ukuran gambar maksimal %d MB.', intdiv($image['max_kb'], 1024)),
            'image.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
        ];
    }
}
