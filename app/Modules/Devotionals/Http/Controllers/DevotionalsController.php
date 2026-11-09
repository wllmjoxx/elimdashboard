<?php

namespace App\Modules\Devotionals\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Devotionals\Enums\DevotionalStatus;
use App\Modules\Devotionals\Http\Requests\SaveDevotionalRequest;
use App\Modules\Devotionals\Models\Devotional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin panel for devotionals (PRD 4.8): Pelayan writes drafts, Admin
 * reviews and publishes them today or on a scheduled date.
 */
class DevotionalsController extends Controller
{
    private const TABS = [
        'draft' => 'Draf',
        'scheduled' => 'Terjadwal',
        'published' => 'Terbit',
    ];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Devotional::class);

        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'draft';
        $today = Devotional::today()->toDateString();

        $tabQuery = fn (string $name) => match ($name) {
            'draft' => Devotional::query()->where('status', DevotionalStatus::Draft->value),
            'scheduled' => Devotional::query()->where('status', DevotionalStatus::Published->value)->whereDate('publish_date', '>', $today),
            default => Devotional::query()->where('status', DevotionalStatus::Published->value)->whereDate('publish_date', '<=', $today),
        };

        $devotionals = $tabQuery($tab)
            ->with('author:id,name')
            ->orderBy('publish_date', $tab === 'published' ? 'desc' : 'asc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Devotional $devotional) => [
                ...$this->summary($devotional),
                'can' => [
                    'update' => $request->user()->can('update', $devotional),
                    'publish' => $request->user()->can('publish', $devotional),
                    'delete' => $request->user()->can('delete', $devotional),
                ],
            ]);

        return Inertia::render('devotionals/index', [
            'tab' => $tab,
            'tabs' => collect(self::TABS)->map(fn (string $label, string $name) => [
                'value' => $name,
                'label' => $label,
                'count' => $tabQuery($name)->count(),
            ])->values(),
            'devotionals' => $devotionals,
            'canCreate' => $request->user()->can('create', Devotional::class),
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Devotional::class);

        return Inertia::render('devotionals/form', [
            'devotional' => null,
            'defaultDate' => Devotional::today()->toDateString(),
            'canPublish' => $request->user()->can('devotionals.publish'),
        ]);
    }

    public function store(SaveDevotionalRequest $request): RedirectResponse
    {
        Gate::authorize('create', Devotional::class);

        $devotional = new Devotional($request->validated());
        $devotional->created_by = $request->user()->id;
        $devotional->save();

        if ($request->boolean('publish') && $request->user()->can('publish', $devotional)) {
            $this->markPublished($devotional, $request);
        }

        return to_route('devotionals.index', ['tab' => $this->tabOf($devotional)])
            ->with('status', $devotional->status === DevotionalStatus::Draft
                ? 'Draf renungan tersimpan. Admin akan meninjaunya sebelum terbit.'
                : 'Renungan diterbitkan.');
    }

    public function edit(Request $request, Devotional $devotional): Response
    {
        Gate::authorize('update', $devotional);

        return Inertia::render('devotionals/form', [
            'devotional' => [
                ...$this->summary($devotional),
                'scriptureText' => $devotional->scripture_text,
                'body' => $devotional->body,
            ],
            'defaultDate' => Devotional::today()->toDateString(),
            'canPublish' => $request->user()->can('publish', $devotional),
        ]);
    }

    public function update(SaveDevotionalRequest $request, Devotional $devotional): RedirectResponse
    {
        Gate::authorize('update', $devotional);

        $devotional->update($request->validated());

        if ($request->boolean('publish') && $request->user()->can('publish', $devotional)) {
            $this->markPublished($devotional, $request);
        }

        return to_route('devotionals.index', ['tab' => $this->tabOf($devotional)])
            ->with('status', 'Renungan tersimpan.');
    }

    public function publish(Request $request, Devotional $devotional): RedirectResponse
    {
        Gate::authorize('publish', $devotional);

        $this->markPublished($devotional, $request);

        return back()->with('status', $devotional->isScheduled()
            ? "\"{$devotional->title}\" dijadwalkan terbit {$devotional->publish_date->locale('id')->translatedFormat('j F Y')}."
            : "\"{$devotional->title}\" diterbitkan.");
    }

    public function unpublish(Devotional $devotional): RedirectResponse
    {
        Gate::authorize('publish', $devotional);

        $devotional->forceFill(['status' => DevotionalStatus::Draft, 'published_by' => null, 'published_at' => null])->save();

        return back()->with('status', "\"{$devotional->title}\" dikembalikan menjadi draf.");
    }

    public function destroy(Devotional $devotional): RedirectResponse
    {
        Gate::authorize('delete', $devotional);

        $devotional->delete();

        return back()->with('status', "\"{$devotional->title}\" dihapus.");
    }

    private function markPublished(Devotional $devotional, Request $request): void
    {
        $devotional->forceFill([
            'status' => DevotionalStatus::Published,
            'published_by' => $request->user()->id,
            'published_at' => now(),
        ])->save();
    }

    private function tabOf(Devotional $devotional): string
    {
        return match (true) {
            $devotional->status === DevotionalStatus::Draft => 'draft',
            $devotional->isScheduled() => 'scheduled',
            default => 'published',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Devotional $devotional): array
    {
        return [
            'id' => $devotional->id,
            'title' => $devotional->title,
            'slug' => $devotional->slug,
            'scriptureRef' => $devotional->scripture_ref,
            'publishDate' => $devotional->publish_date->toDateString(),
            'status' => $devotional->status->value,
            'stateLabel' => $devotional->stateLabel(),
            'author' => $devotional->author?->name,
        ];
    }
}
