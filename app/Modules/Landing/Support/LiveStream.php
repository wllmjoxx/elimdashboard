<?php

namespace App\Modules\Landing\Support;

use Illuminate\Support\Carbon;

/**
 * The live stream link Admin sets for the homepage. YouTube links are shown
 * as an embedded player; any other link becomes a "Tonton live" button.
 */
class LiveStream
{
    /**
     * Embeddable player address for a YouTube link, or null when the link
     * cannot be embedded (another site, a @handle page).
     */
    public static function embedUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower(preg_replace('/^(www\.|m\.)/', '', $parts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        $id = null;

        if ($host === 'youtu.be') {
            $id = trim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            parse_str($parts['query'] ?? '', $query);

            if (preg_match('#^/channel/(UC[A-Za-z0-9_-]{22})#', $path, $match)) {
                return 'https://www.youtube.com/embed/live_stream?channel='.$match[1].'&autoplay=1&mute=1&playsinline=1';
            }

            $id = is_string($query['v'] ?? null)
                ? $query['v']
                : (preg_match('#^/(?:live|embed|shorts)/([^/?]+)#', $path, $match) ? $match[1] : null);
        }

        if ($id === null || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$id.'?autoplay=1&mute=1&playsinline=1&rel=0';
    }

    /**
     * Whether the stream should show now: marked live and not left on past
     * the automatic cut-off.
     *
     * @param  array<string, mixed>  $live
     */
    public static function isOn(array $live): bool
    {
        if (! ($live['is_live'] ?? false) || blank($live['url'] ?? null)) {
            return false;
        }

        $started = isset($live['started_at']) ? Carbon::parse($live['started_at']) : null;

        return $started === null
            || $started->gt(now()->subHours((int) config('modules.landing.live.auto_off_hours')));
    }

    /**
     * Props for the public hero frame.
     *
     * @param  array<string, mixed>  $live
     * @return array{is_live: bool, title: string|null, url: string|null, embed_url: string|null}
     */
    public static function present(array $live): array
    {
        $on = self::isOn($live);
        $url = $live['url'] ?? null;

        return [
            'is_live' => $on,
            'title' => $on ? ($live['title'] ?? null) : null,
            'url' => $on ? $url : null,
            'embed_url' => $on ? self::embedUrl($url) : null,
        ];
    }
}
