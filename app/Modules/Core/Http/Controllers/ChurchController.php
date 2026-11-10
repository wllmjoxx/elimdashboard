<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Church;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Branch management for Superadmin (PRD 4.8, 4.10). Branches are
 * deactivated, never deleted, because their data stays.
 */
class ChurchController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $church = new Church;
        $church->fill($this->validated($request))->save();

        AuditLog::record('core.church.created', $church, ['name' => $church->name]);

        return back()->with('status', "Cabang \"{$church->name}\" ditambahkan.");
    }

    public function update(Request $request, Church $church): RedirectResponse
    {
        $validated = $this->validated($request, $church);

        // The default branch serves visitors; it must stay active.
        if ($church->slug === config('modules.core.default_church') && ! $validated['is_active']) {
            return back()->withErrors(['is_active' => 'Cabang utama tidak bisa dinonaktifkan.']);
        }

        $church->fill($validated)->save();

        AuditLog::record('core.church.updated', $church, ['changes' => array_keys($church->getChanges())]);

        return back()->with('status', "Cabang \"{$church->name}\" tersimpan.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Church $church = null): array
    {
        $request->merge(['slug' => Str::slug((string) ($request->input('slug') ?: $request->input('name')))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:80', Rule::unique('churches', 'slug')->ignore($church)],
            'address' => ['nullable', 'string', 'max:300'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{8,30}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['required', Rule::in(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'])],
            'is_active' => ['boolean'],
        ], [
            'whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, +, -, dan kurung.',
            'slug.unique' => 'Alamat singkat ini sudah dipakai cabang lain.',
        ], ['name' => 'nama cabang', 'slug' => 'alamat singkat', 'address' => 'alamat', 'timezone' => 'zona waktu']);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'address' => $validated['address'] ?? null,
            'contact' => array_filter([
                'whatsapp' => $validated['whatsapp'] ?? null,
                'email' => $validated['email'] ?? null,
            ]),
            'timezone' => $validated['timezone'],
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
