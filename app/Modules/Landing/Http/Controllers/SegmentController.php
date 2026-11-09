<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landing\Http\Requests\UpdateSegmentRequest;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class SegmentController extends Controller
{
    /**
     * Save one of the three home segments, replacing or removing its
     * background image when asked.
     */
    public function update(UpdateSegmentRequest $request, string $segment): RedirectResponse
    {
        $section = SiteSection::query()->where('key', $segment)->first()
            ?? new SiteSection([
                'key' => $segment,
                'sort_order' => SiteSection::query()->max('sort_order') + 10,
            ]);

        $config = config('modules.landing.segment_image');
        $disk = Storage::disk($config['disk']);
        $image = $section->content['image'] ?? null;

        if ($image && ($request->boolean('remove_image') || $request->hasFile('image'))) {
            $disk->delete($image);
            $image = null;
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image')->store($config['directory'], $config['disk']);
        }

        $section->content = [
            ...$request->safe()->only(['eyebrow', 'title', 'text', 'button_label', 'button_href', 'align']),
            'image' => $image,
        ];
        $section->is_active = $request->boolean('is_active');
        $section->save();

        return back();
    }
}
