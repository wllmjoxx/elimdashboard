<?php

namespace App\Modules\Prayer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Captcha;
use App\Modules\Landing\Support\LandingContent;
use App\Modules\Members\Models\Member;
use App\Modules\Prayer\Enums\PrayerCategory;
use App\Modules\Prayer\Http\Requests\StorePrayerRequest;
use App\Modules\Prayer\Models\PrayerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public Form Doa (PRD 4.6) for guests and signed-in members, and the
 * member's own prayer history.
 */
class PrayerFormController extends Controller
{
    public function create(Request $request, Captcha $captcha, LandingContent $landing): Response
    {
        $user = $request->user();
        $intro = collect($landing->sections(['prayer']))->first()['content'] ?? [];

        return Inertia::render('prayer/public/form', [
            'intro' => [
                'title' => $intro['title'] ?? 'Butuh didoakan?',
                'text' => $intro['text'] ?? 'Tim Doa GGP Elim mendoakan setiap pokok doa yang masuk setiap minggu.',
                'verse' => $intro['verse'] ?? 'Dan apa saja yang kamu minta dalam doa dengan penuh kepercayaan, kamu akan menerimanya.',
                'reference' => $intro['reference'] ?? 'Matius 21:22',
            ],
            'categories' => array_map(fn (PrayerCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ], PrayerCategory::cases()),
            'prefill' => $user ? $this->prefill($user->id) : null,
            'remaining' => $user ? $this->remaining($user->id) : null,
            'captchaSiteKey' => $user ? null : $captcha->siteKey(),
            'confirmation' => $request->session()->get('prayer_sent'),
        ])->withViewData('seo', [
            'title' => 'Form Doa',
            'description' => 'Kirimkan pokok doa Anda. Tim Doa GGP Elim Ciputat akan mendoakannya setiap minggu.',
        ]);
    }

    public function store(StorePrayerRequest $request): RedirectResponse
    {
        $prayer = new PrayerRequest($request->safe()->except(['website', 'captcha_token']));
        $prayer->user_id = $request->user()?->id;
        $prayer->save();

        $message = 'Terima kasih. Pokok doa Anda sudah kami terima dan akan didoakan oleh Tim Doa GGP Elim minggu ini.';

        return $request->user()
            ? to_route('prayer.history')->with('status', $message)
            : to_route('landing.prayer')->with('prayer_sent', $message);
    }

    public function history(Request $request): Response
    {
        $requests = PrayerRequest::query()->withoutGlobalScopes()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15)
            ->through(fn (PrayerRequest $prayer) => [
                'id' => $prayer->id,
                'body' => $prayer->body,
                'category' => $prayer->category->label(),
                'status' => $prayer->status->value,
                'statusLabel' => $prayer->status->label(),
                'sentAt' => $prayer->created_at->toIso8601String(),
            ]);

        return Inertia::render('prayer/history', [
            'requests' => $requests,
            'remaining' => $this->remaining($request->user()->id),
            'flash' => $request->session()->get('status'),
        ]);
    }

    /**
     * Fields filled from the member's account and profile (PRD 4.6).
     *
     * @return array<string, string|int|null>
     */
    private function prefill(int $userId): array
    {
        $member = Member::query()->with('profile')->find($userId);
        $birthDate = $member?->profile?->birth_date;

        return [
            'full_name' => $member?->name,
            'phone' => $member?->phone,
            'address' => $member?->profile?->address,
            'age' => $birthDate ? (int) $birthDate->diffInYears(now()) : null,
        ];
    }

    private function remaining(int $userId): int
    {
        $sent = PrayerRequest::query()->withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereDate('week_start', PrayerRequest::currentWeekStart()->toDateString())
            ->count();

        return max(0, (int) config('modules.prayer.member_weekly_limit') - $sent);
    }
}
