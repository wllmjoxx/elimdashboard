<?php

namespace App\Modules\Testimonies\Http\Requests;

use App\Modules\Core\Rules\ValidCaptcha;
use App\Modules\Testimonies\Enums\TestimonyCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Anonymous testimony form (PRD 4.7). Captcha and honeypot always apply,
 * signed in or not, because the sender is never recorded.
 */
class StoreTestimonyRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'min:20', 'max:5000'],
            'category' => ['required', Rule::enum(TestimonyCategory::class)],
            'display_name' => ['nullable', 'string', 'max:60'],
            'website' => ['prohibited'],
            'captcha_token' => [new ValidCaptcha],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'body' => 'kesaksian',
            'category' => 'kategori',
            'display_name' => 'nama tampilan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website.prohibited' => 'Permintaan tidak dapat diproses.',
        ];
    }
}
