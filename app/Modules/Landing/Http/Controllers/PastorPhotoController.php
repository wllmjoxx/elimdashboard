<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Photo of the senior pastor for the "Gembala Sidang" section.
 */
class PastorPhotoController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $config = config('modules.landing.pastor_photo');

        $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:'.implode(',', $config['mimes']),
                'max:'.$config['max_kb'],
                Rule::dimensions()->minWidth($config['min_width'])->minHeight($config['min_height']),
            ],
        ], [], ['photo' => 'foto']);

        $section = $this->section();
        $disk = Storage::disk($config['disk']);

        if ($old = $section->content['photo'] ?? null) {
            $disk->delete($old);
        }

        $section->content = [
            ...$section->content,
            'photo' => $request->file('photo')->store($config['directory'], $config['disk']),
        ];
        $section->save();

        return back()->with('status', 'Foto gembala disimpan.');
    }

    public function destroy(): RedirectResponse
    {
        $section = $this->section();

        if ($old = $section->content['photo'] ?? null) {
            Storage::disk(config('modules.landing.pastor_photo.disk'))->delete($old);
        }

        $section->content = [...$section->content, 'photo' => null];
        $section->save();

        return back()->with('status', 'Foto gembala dihapus.');
    }

    private function section(): SiteSection
    {
        return SiteSection::query()->where('key', 'pastor')->firstOrFail();
    }
}
