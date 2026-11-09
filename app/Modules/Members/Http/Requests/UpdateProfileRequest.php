<?php

namespace App\Modules\Members\Http\Requests;

use App\Modules\Members\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Fields a member edits on their own profile (PRD 4.5). Email stays in
 * Settings, where changing it is verified; membership data is Admin's.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $photo = config('modules.members.photo');

        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{8,30}$/'],
            'birth_date' => ['nullable', 'date', 'after:1900-01-01', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'address' => ['nullable', 'string', 'max:500'],
            'show_birthday' => ['boolean'],
            'show_phone' => ['boolean'],
            'remove_photo' => ['boolean'],
            'photo' => [
                'nullable',
                'image',
                'mimes:'.implode(',', $photo['mimes']),
                'max:'.$photo['max_kb'],
                Rule::dimensions()
                    ->minWidth($photo['min_size'])
                    ->minHeight($photo['min_size'])
                    ->maxWidth($photo['max_size'])
                    ->maxHeight($photo['max_size']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'nickname' => 'nama panggilan',
            'phone' => 'nomor HP/WA',
            'birth_date' => 'tanggal lahir',
            'gender' => 'jenis kelamin',
            'address' => 'alamat',
            'photo' => 'foto',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $photo = config('modules.members.photo');

        return [
            'phone.regex' => 'Nomor HP hanya boleh berisi angka, spasi, tanda +, -, dan kurung.',
            'photo.dimensions' => sprintf('Foto minimal %1$d × %1$d px dan maksimal %2$d × %2$d px.', $photo['min_size'], $photo['max_size']),
            'photo.max' => sprintf('Ukuran foto maksimal %d MB.', intdiv($photo['max_kb'], 1024)),
            'photo.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
        ];
    }
}
