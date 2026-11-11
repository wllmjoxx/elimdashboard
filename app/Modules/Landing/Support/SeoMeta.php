<?php

namespace App\Modules\Landing\Support;

use App\Modules\Landing\Models\SiteSection;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Server-rendered <head> tags: title, description, canonical, Open Graph,
 * geo meta and the JSON-LD "Church" block.
 *
 * Public controllers describe their page with ->withViewData('seo', [...]):
 *
 * - title: page title without the site name ("Renungan Harian")
 * - description: plain text, cut to 160 characters
 * - image: absolute URL of the preview image
 * - type: Open Graph type, "website" (default) or "article"
 * - published_at: for articles
 * - schema: true to add the JSON-LD block (homepage and contact page)
 */
class SeoMeta
{
    public function __construct(private readonly Request $request) {}

    /**
     * Only visitor pages (resources/js/pages/<key>/public/) are indexed.
     */
    public static function isPublic(string $component): bool
    {
        return str_contains($component, '/public/');
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array{title: string, head: string}
     */
    public function build(string $component, array $page): array
    {
        $site = (string) config('app.name');

        if (! self::isPublic($component)) {
            return [
                'title' => $site,
                'head' => '<meta name="robots" content="noindex, nofollow">',
            ];
        }

        $title = filled($page['title'] ?? null) ? "{$page['title']} - {$site}" : $site;
        $description = $this->text($page['description'] ?? null) ?? $this->text(config('modules.landing.seo.description'));
        $image = $page['image'] ?? $this->defaultImage();
        $published = $page['published_at'] ?? null;

        return [
            'title' => $title,
            'head' => view('landing::seo', [
                'title' => $title,
                'site' => $site,
                'description' => $description,
                'canonical' => $this->canonical(),
                'image' => $image,
                'type' => $page['type'] ?? 'website',
                'publishedAt' => $published instanceof DateTimeInterface ? $published->format(DATE_ATOM) : null,
                'indexable' => app()->isProduction(),
                'geo' => config('modules.landing.seo'),
                'schema' => ($page['schema'] ?? false) ? $this->schema($site, $description, $image) : null,
            ])->render(),
        ];
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags($value))), 160);
    }

    /**
     * Current URL without tracking parameters; keeps ?page= beyond page 1.
     */
    private function canonical(): string
    {
        $page = (int) $this->request->query('page', 1);

        return $page > 1 ? $this->request->url().'?page='.$page : $this->request->url();
    }

    /**
     * First uploaded segment image, else the app icon.
     */
    private function defaultImage(): string
    {
        $path = SiteSection::query()
            ->visible()
            ->whereIn('key', SiteSection::SEGMENT_KEYS)
            ->get()
            ->map(fn (SiteSection $section) => $section->content['image'] ?? null)
            ->first(fn (mixed $image) => is_string($image) && $image !== '');

        return LandingContent::imageUrl($path) ?? url('/apple-touch-icon.png');
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(string $site, ?string $description, string $image): array
    {
        $seo = config('modules.landing.seo');
        $contact = SiteSection::query()->visible()->where('key', 'contact')->first()?->content ?? [];

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Church',
            'name' => $site,
            'alternateName' => $seo['legal_name'],
            'url' => url('/'),
            'description' => $description,
            'image' => $image,
            'logo' => url('/apple-touch-icon.png'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $seo['address']['street'],
                'addressLocality' => $seo['address']['locality'],
                'addressRegion' => $seo['address']['region'],
                'postalCode' => $seo['address']['postal_code'],
                'addressCountry' => $seo['address']['country'],
            ],
        ];

        if (($phone = $this->phone($contact['whatsapp'] ?? null)) !== null) {
            $schema['telephone'] = $phone;
        }

        if (filled($contact['maps_query'] ?? null)) {
            $schema['hasMap'] = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode((string) $contact['maps_query']);
        }

        if (filled($seo['latitude']) && filled($seo['longitude'])) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $seo['latitude'],
                'longitude' => (float) $seo['longitude'],
            ];
        }

        return $schema;
    }

    /**
     * "0858 8385 8048" -> "+6285883858048".
     */
    private function phone(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === null || strlen($digits) < 8) {
            return null;
        }

        return '+'.(str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits);
    }
}
