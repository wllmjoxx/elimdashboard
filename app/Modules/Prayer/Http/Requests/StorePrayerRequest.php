<?php

namespace App\Modules\Prayer\Http\Requests;

use App\Modules\Core\Rules\ValidCaptcha;
use App\Modules\Prayer\Enums\PrayerCategory;
use App\Modules\Prayer\Models\PrayerRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Form Doa (PRD 4.6): full name is required; guests pass the captcha and a
 * honeypot; members may send a limited number of requests per week.
 */
class StorePrayerRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{8,30}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'min:5', 'max:3000'],
            'category' => ['required', Rule::enum(PrayerCategory::class)],
            // Bots fill every field; people never see this one.
            'website' => ['prohibited'],
            ...($this->user() ? [] : ['captcha_token' => [new ValidCaptcha]]),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->user();

                if (! $user) {
                    return;
                }

                $limit = (int) config('modules.prayer.member_weekly_limit');
                $sent = PrayerRequest::query()->withoutGlobalScopes()
                    ->where('user_id', $user->id)
                    ->whereDate('week_start', PrayerRequest::currentWeekStart()->toDateString())
                    ->count();

                if ($sent >= $limit) {
                    $validator->errors()->add('body', "Anda sudah mengirim {$limit} pokok doa minggu ini. Silakan kirim lagi minggu depan.");
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
            'full_name' => 'nama lengkap',
            'age' => 'usia',
            'phone' => 'nomor HP/WA',
            'address' => 'alamat',
            'body' => 'pokok doa',
            'category' => 'kategori',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor HP hanya boleh berisi angka, spasi, tanda +, -, dan kurung.',
            'website.prohibited' => 'Permintaan tidak dapat diproses.',
        ];
    }
}
