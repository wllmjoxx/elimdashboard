<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gallery\Models\Album;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The homepage gallery section (PRD 4.8): which album it shows and in
 * which layout.
 */
class GallerySettingsController extends Controller
{
    public const LAYOUTS = ['grid', 'carousel', 'masonry'];

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Album::query() is branch scoped, so only own albums pass.
            'album_id' => ['nullable', 'integer', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value !== null && ! Album::query()->whereKey($value)->exists()) {
                    $fail('Album tidak ditemukan.');
                }
            }],
            'layout' => ['required', Rule::in(self::LAYOUTS)],
        ], [], ['album_id' => 'album', 'layout' => 'tata letak']);

        $section = SiteSection::query()->where('key', 'gallery')->first()
            ?? new SiteSection(['key' => 'gallery', 'content' => ['title' => 'Galeri'], 'sort_order' => 90]);

        $section->content = [
            ...$section->content,
            'album_id' => $validated['album_id'] ?? null,
            'layout' => $validated['layout'],
        ];
        $section->save();

        return back()->with('status', 'Pengaturan galeri Home tersimpan.');
    }
}
