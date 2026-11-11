<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Modules\ModuleRegistry;
use DateTimeInterface;
use Illuminate\Http\Response;

/**
 * /sitemap.xml (pages from every enabled module) and /robots.txt.
 */
class SeoController extends Controller
{
    public function sitemap(ModuleRegistry $modules): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($modules->sitemap() as $entry) {
            $xml .= '  <url><loc>'.e($entry['loc']).'</loc>';

            if (($entry['lastmod'] ?? null) instanceof DateTimeInterface) {
                $xml .= '<lastmod>'.$entry['lastmod']->format('Y-m-d').'</lastmod>';
            }

            $xml .= "</url>\n";
        }

        $xml .= "</urlset>\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (app()->isProduction()) {
            foreach (config('modules.landing.seo.disallow') as $path) {
                $lines[] = 'Disallow: '.$path;
            }
        } else {
            $lines[] = 'Disallow: /';
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('landing.sitemap');

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
