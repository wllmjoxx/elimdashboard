<?php

namespace App\Modules\Prayer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Prayer\Enums\PrayerCategory;
use App\Modules\Prayer\Enums\PrayerStatus;
use App\Modules\Prayer\Models\PrayerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tim Doa panel (PRD 4.6): prayer requests per week, filters, marking as
 * prayed or answered, and a printable weekly list. Every read is written to
 * the audit log.
 */
class PrayerController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PrayerRequest::class);

        $week = $this->week($request);
        $filters = $this->filters($request);
        $requests = $this->requests($week, $filters);

        AuditLog::record('prayer.week.viewed', metadata: [
            'week_start' => $week->toDateString(),
            'filters' => array_filter($filters),
            'count' => $requests->count(),
        ]);

        $counts = PrayerRequest::query()
            ->whereDate('week_start', $week->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('prayer/index', [
            'week' => $this->weekProps($week),
            'filters' => $filters,
            'requests' => $requests->map(fn (PrayerRequest $prayer) => [
                ...$this->present($prayer),
                'can' => [
                    'mark' => $request->user()->can('mark', $prayer),
                    'delete' => $request->user()->can('delete', $prayer),
                ],
            ])->values(),
            'statuses' => array_map(fn (PrayerStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ], PrayerStatus::cases()),
            'categories' => array_map(fn (PrayerCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ], PrayerCategory::cases()),
            'canExport' => $request->user()->can('export', PrayerRequest::class),
            'flash' => $request->session()->get('status'),
        ]);
    }

    /**
     * Printable weekly list; the browser's print dialog saves it as PDF.
     */
    public function print(Request $request): View
    {
        Gate::authorize('export', PrayerRequest::class);

        $week = $this->week($request);
        $filters = $this->filters($request);
        $requests = $this->requests($week, $filters);

        AuditLog::record('prayer.week.exported', metadata: [
            'week_start' => $week->toDateString(),
            'filters' => array_filter($filters),
            'count' => $requests->count(),
        ]);

        return view('prayer::print', [
            'week' => $this->weekProps($week),
            'requests' => $requests->map(fn (PrayerRequest $prayer) => $this->present($prayer))->values()->all(),
            'printedAt' => now((string) config('modules.prayer.timezone'))->locale('id')->translatedFormat('j F Y, H.i'),
        ]);
    }

    public function updateStatus(Request $request, PrayerRequest $prayerRequest): RedirectResponse
    {
        Gate::authorize('mark', $prayerRequest);

        $status = PrayerStatus::from($request->validate([
            'status' => ['required', Rule::enum(PrayerStatus::class)],
        ])['status']);

        $prayerRequest->status = $status;

        if ($status === PrayerStatus::Prayed && $prayerRequest->prayed_at === null) {
            $prayerRequest->prayed_by = $request->user()->id;
            $prayerRequest->prayed_at = now();
        }

        if ($status === PrayerStatus::Answered) {
            $prayerRequest->answered_at ??= now();
        }

        $prayerRequest->save();

        AuditLog::record('prayer.status.changed', $prayerRequest, ['status' => $status->value]);

        return back()->with('status', "Pokok doa ditandai: {$status->label()}.");
    }

    public function destroy(PrayerRequest $prayerRequest): RedirectResponse
    {
        Gate::authorize('delete', $prayerRequest);

        AuditLog::record('prayer.deleted', $prayerRequest, ['week_start' => $prayerRequest->week_start->toDateString()]);
        $prayerRequest->delete();

        return back()->with('status', 'Pokok doa dihapus.');
    }

    private function week(Request $request): Carbon
    {
        $value = $request->query('week');

        try {
            return PrayerRequest::weekStartOf(is_string($value) && $value !== '' ? $value : null);
        } catch (\Throwable) {
            return PrayerRequest::currentWeekStart();
        }
    }

    /**
     * @return array{status: string|null, category: string|null}
     */
    private function filters(Request $request): array
    {
        return [
            'status' => PrayerStatus::tryFrom((string) $request->query('status'))?->value,
            'category' => PrayerCategory::tryFrom((string) $request->query('category'))?->value,
        ];
    }

    /**
     * @param  array{status: string|null, category: string|null}  $filters
     * @return Collection<int, PrayerRequest>
     */
    private function requests(Carbon $week, array $filters): Collection
    {
        return PrayerRequest::query()
            ->whereDate('week_start', $week->toDateString())
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'], fn ($query, $category) => $query->where('category', $category))
            ->with('prayedBy:id,name')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @return array<string, string|bool>
     */
    private function weekProps(Carbon $week): array
    {
        $end = $week->copy()->addDays(6);
        $current = PrayerRequest::currentWeekStart();

        return [
            'start' => $week->toDateString(),
            'label' => $week->locale('id')->translatedFormat('j M').' – '.$end->locale('id')->translatedFormat('j M Y'),
            'previous' => $week->copy()->subWeek()->toDateString(),
            'next' => $week->copy()->addWeek()->toDateString(),
            'isCurrent' => $week->equalTo($current),
            'hasNext' => $week->lessThan($current),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(PrayerRequest $prayer): array
    {
        return [
            'id' => $prayer->id,
            'fullName' => $prayer->full_name,
            'age' => $prayer->age,
            'phone' => $prayer->phone,
            'address' => $prayer->address,
            'body' => $prayer->body,
            'category' => $prayer->category->label(),
            'status' => $prayer->status->value,
            'statusLabel' => $prayer->status->label(),
            'isMember' => $prayer->user_id !== null,
            'prayedBy' => $prayer->prayedBy?->name,
            'sentAt' => $prayer->created_at->toIso8601String(),
        ];
    }
}
