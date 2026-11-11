<?php

namespace App\Modules\Landing\Http\Requests;

use App\Modules\Landing\Models\HeroVideo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHeroVideoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $config = config('modules.landing.hero_video');

        return [
            'video' => [
                'required',
                'file',
                'mimetypes:'.implode(',', $config['mimetypes']),
                'extensions:'.implode(',', $config['extensions']),
                'max:'.$config['max_kb'],
            ],
            'title' => ['nullable', 'string', 'max:120'],
            'poster' => ['nullable', 'image', 'mimes:'.implode(',', $config['poster_mimes']), 'max:'.$config['poster_max_kb']],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $max = (int) config('modules.landing.hero_video.max_count');

                if (HeroVideo::query()->count() >= $max) {
                    $validator->errors()->add('video', "Maksimal {$max} video. Hapus salah satu dulu.");
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $mb = (int) round(config('modules.landing.hero_video.max_kb') / 1024);

        return [
            'video.uploaded' => "Video gagal diunggah. Pastikan ukurannya tidak lebih dari {$mb} MB.",
            'video.max' => "Ukuran video maksimal {$mb} MB.",
            'video.mimetypes' => 'Video harus berformat MP4 atau WebM.',
            'video.extensions' => 'Video harus berformat MP4 atau WebM.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'video' => 'video',
            'title' => 'judul',
            'poster' => 'gambar sampul',
        ];
    }
}
