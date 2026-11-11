<?php

namespace App\Modules\Gallery\Support;

use App\Modules\Gallery\Models\GallerySetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Posts from the church's Instagram account through the official Instagram
 * API (Instagram Login, professional accounts). Posts are cached so the
 * site stays fast and within Instagram's rate limits; a failed fetch keeps
 * showing the last good posts.
 */
class InstagramFeed
{
    private const CACHE_KEY = 'gallery.instagram.posts';

    private const LAST_GOOD_KEY = 'gallery.instagram.posts.last-good';

    /**
     * Checks a token and returns the account it belongs to.
     *
     * @return array{user_id: string, username: string}
     *
     * @throws RequestException
     */
    public function profile(string $token): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->timeout(10)
            ->get('/me', ['fields' => 'user_id,username', 'access_token' => $token])
            ->throw()
            ->json();

        return [
            'user_id' => (string) ($response['user_id'] ?? $response['id'] ?? ''),
            'username' => (string) ($response['username'] ?? ''),
        ];
    }

    /**
     * Exchanges a long-lived token for a fresh one (valid another 60 days).
     *
     * @return array{token: string, expires_at: Carbon}
     *
     * @throws RequestException
     */
    public function refresh(string $token): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->timeout(10)
            ->get('/refresh_access_token', ['grant_type' => 'ig_refresh_token', 'access_token' => $token])
            ->throw()
            ->json();

        return [
            'token' => (string) $response['access_token'],
            'expires_at' => now()->addSeconds((int) ($response['expires_in'] ?? 5184000)),
        ];
    }

    /**
     * Latest posts as gallery items, newest first.
     *
     * @return list<array{id: string, caption: string|null, image: string, permalink: string, type: string, takenAt: string|null}>
     */
    public function posts(?GallerySetting $setting = null): array
    {
        $setting ??= GallerySetting::current();

        if (! $setting->usesInstagram()) {
            return [];
        }

        $minutes = (int) config('modules.gallery.instagram.cache_minutes');

        return Cache::remember(self::CACHE_KEY, now()->addMinutes($minutes), function () use ($setting) {
            try {
                $posts = $this->fetch((string) $setting->instagram_token);
                Cache::forever(self::LAST_GOOD_KEY, $posts);

                return $posts;
            } catch (Throwable $exception) {
                Log::warning('Instagram feed could not be loaded.', ['error' => $exception->getMessage()]);

                return Cache::get(self::LAST_GOOD_KEY, []);
            }
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::LAST_GOOD_KEY);
    }

    /**
     * @return list<array{id: string, caption: string|null, image: string, permalink: string, type: string, takenAt: string|null}>
     */
    private function fetch(string $token): array
    {
        $items = Http::baseUrl($this->baseUrl())
            ->timeout(10)
            ->get('/me/media', [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
                'limit' => (int) config('modules.gallery.instagram.limit'),
                'access_token' => $token,
            ])
            ->throw()
            ->json('data', []);

        $posts = [];

        foreach ($items as $item) {
            // Videos show their cover image; albums show their first photo.
            $image = $item['media_type'] === 'VIDEO' ? ($item['thumbnail_url'] ?? null) : ($item['media_url'] ?? null);

            if (! is_string($image) || $image === '' || ! isset($item['permalink'])) {
                continue;
            }

            $posts[] = [
                'id' => (string) $item['id'],
                'caption' => isset($item['caption']) ? mb_strimwidth((string) $item['caption'], 0, 300, '…') : null,
                'image' => $image,
                'permalink' => (string) $item['permalink'],
                'type' => (string) $item['media_type'],
                'takenAt' => $item['timestamp'] ?? null,
            ];
        }

        return $posts;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('modules.gallery.instagram.graph_url'), '/');
    }
}
