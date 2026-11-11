<?php

namespace App\Modules\Gallery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gallery\Models\Album;
use App\Modules\Gallery\Models\GallerySetting;
use App\Modules\Gallery\Support\InstagramFeed;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Admin › Galeri › Sumber galeri: albums uploaded here or the church's
 * Instagram account.
 */
class GallerySourceController extends Controller
{
    public function __construct(private readonly InstagramFeed $instagram) {}

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('manage', Album::class);

        $data = $request->validate([
            'source' => ['required', Rule::in([GallerySetting::SOURCE_MANUAL, GallerySetting::SOURCE_INSTAGRAM])],
            'token' => ['nullable', 'string', 'max:1000'],
        ], [], ['source' => 'sumber galeri', 'token' => 'access token']);

        $setting = GallerySetting::current();

        if (filled($data['token'] ?? null)) {
            try {
                $profile = $this->instagram->profile(trim($data['token']));
            } catch (RequestException) {
                throw ValidationException::withMessages([
                    'token' => 'Instagram menolak token ini. Pastikan token masih berlaku dan dibuat untuk akun Instagram profesional gereja.',
                ]);
            }

            $setting->fill([
                'instagram_token' => trim($data['token']),
                'instagram_user_id' => $profile['user_id'],
                'instagram_username' => $profile['username'],
                // Long-lived tokens last 60 days; the daily job renews them.
                'instagram_expires_at' => now()->addDays(60),
            ]);
        }

        if ($data['source'] === GallerySetting::SOURCE_INSTAGRAM && blank($setting->instagram_token)) {
            throw ValidationException::withMessages(['token' => 'Tempel access token Instagram untuk memakai mode Instagram.']);
        }

        $setting->source = $data['source'];
        $setting->save();
        $this->instagram->forget();

        return back()->with('status', $setting->source === GallerySetting::SOURCE_INSTAGRAM
            ? "Galeri menampilkan postingan Instagram @{$setting->instagram_username}."
            : 'Galeri menampilkan album yang diunggah di sini.');
    }

    public function disconnect(): RedirectResponse
    {
        Gate::authorize('manage', Album::class);

        GallerySetting::current()->update([
            'source' => GallerySetting::SOURCE_MANUAL,
            'instagram_token' => null,
            'instagram_user_id' => null,
            'instagram_username' => null,
            'instagram_expires_at' => null,
        ]);
        $this->instagram->forget();

        return back()->with('status', 'Akun Instagram diputuskan. Galeri kembali memakai album.');
    }
}
