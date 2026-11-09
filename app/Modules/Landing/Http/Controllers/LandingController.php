<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gallery\Support\GalleryFeed;
use App\Modules\Landing\Models\SiteSection;
use App\Modules\Landing\Support\LandingContent;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    /**
     * Admin page: edit the three home segments and the gallery section.
     */
    public function index(GalleryFeed $gallery): Response
    {
        $gallerySection = SiteSection::query()->where('key', 'gallery')->first();

        $sections = SiteSection::query()->whereIn('key', SiteSection::SEGMENT_KEYS)->get()->keyBy('key');

        $segments = array_map(function (string $key, int $index) use ($sections) {
            $section = $sections->get($key);
            $content = $section?->content ?? [];

            return [
                'key' => $key,
                'label' => 'Segmen '.($index + 1),
                'eyebrow' => $content['eyebrow'] ?? '',
                'title' => $content['title'] ?? '',
                'text' => $content['text'] ?? '',
                'button_label' => $content['button_label'] ?? '',
                'button_href' => $content['button_href'] ?? '',
                'align' => $content['align'] ?? 'left',
                'is_active' => $section?->is_active ?? false,
                'image_url' => LandingContent::imageUrl($content['image'] ?? null),
            ];
        }, SiteSection::SEGMENT_KEYS, array_keys(SiteSection::SEGMENT_KEYS));

        $image = config('modules.landing.segment_image');

        return Inertia::render('landing/index', [
            'segments' => $segments,
            'gallery' => [
                'albums' => $gallery->picker(),
                'albumId' => $gallerySection?->content['album_id'] ?? null,
                'layout' => $gallerySection?->content['layout'] ?? 'grid',
            ],
            'imageRules' => [
                'formats' => 'JPG, PNG, atau WebP',
                'accept' => implode(',', array_map(fn (string $mime) => '.'.$mime, $image['mimes'])),
                'maxMb' => intdiv($image['max_kb'], 1024),
                'minWidth' => $image['min_width'],
                'minHeight' => $image['min_height'],
                'maxWidth' => $image['max_width'],
                'maxHeight' => $image['max_height'],
                'minRatio' => '3:2',
                'recommended' => $image['recommended'],
            ],
        ]);
    }
}
