<?php

namespace App\Modules\Members\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterMemberRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{8,30}$/'],
            'church_id' => ['required', 'integer', Rule::exists('churches', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
            'phone' => 'nomor HP',
            'church_id' => 'cabang',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk atau gunakan "Lupa password".',
            'phone.regex' => 'Nomor HP hanya boleh berisi angka, spasi, tanda +, -, dan kurung.',
        ];
    }
}
