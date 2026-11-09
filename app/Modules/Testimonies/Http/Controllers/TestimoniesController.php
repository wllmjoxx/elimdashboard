<?php

namespace App\Modules\Testimonies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Testimonies\Enums\TestimonyCategory;
use App\Modules\Testimonies\Enums\TestimonyStatus;
use App\Modules\Testimonies\Models\Testimony;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Moderation panel (PRD 4.7): approve, reject with an internal note, mark
 * for revision, edit the text before publishing, and pick the testimonies
 * shown on the homepage.
 */
class TestimoniesController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Testimony::class);

        $status = TestimonyStatus::tryFrom((string) $request->query('status')) ?? TestimonyStatus::Pending;
        $limitHours = (int) config('modules.testimonies.moderation_hours');

        $counts = Testimony::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $testimonies = Testimony::query()
            ->where('status', $status->value)
            ->with('moderator:id,name')
            ->orderBy($status === TestimonyStatus::Approved ? 'moderated_at' : 'created_at', $status === TestimonyStatus::Approved ? 'desc' : 'asc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Testimony $testimony) => [
                'id' => $testimony->id,
                'title' => $testimony->title,
                'body' => $testimony->body,
                'category' => $testimony->category->value,
                'categoryLabel' => $testimony->category->label(),
                'displayName' => $testimony->display_name,
                'status' => $testimony->status->value,
                'isFeatured' => $testimony->is_featured,
                'note' => $testimony->moderation_note,
                'moderator' => $testimony->moderator?->name,
                'receivedAt' => $testimony->created_at->toIso8601String(),
                'overdue' => $testimony->status === TestimonyStatus::Pending
                    && $testimony->created_at->lt(now()->subHours($limitHours)),
            ]);

        return Inertia::render('testimonies/index', [
            'status' => $status->value,
            'statuses' => array_map(fn (TestimonyStatus $item) => [
                'value' => $item->value,
                'label' => $item->label(),
                'count' => (int) ($counts[$item->value] ?? 0),
            ], TestimonyStatus::cases()),
            'categories' => array_map(fn (TestimonyCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ], TestimonyCategory::cases()),
            'testimonies' => $testimonies,
            'moderationHours' => $limitHours,
            'flash' => $request->session()->get('status'),
        ]);
    }

    /**
     * Edit the wording before publishing, e.g. to remove details that could
     * identify someone.
     */
    public function update(Request $request, Testimony $testimony): RedirectResponse
    {
        Gate::authorize('moderate', $testimony);

        $testimony->update($request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::enum(TestimonyCategory::class)],
            'display_name' => ['nullable', 'string', 'max:60'],
        ]));

        return back()->with('status', 'Kesaksian diperbarui.');
    }

    public function moderate(Request $request, Testimony $testimony): RedirectResponse
    {
        Gate::authorize('moderate', $testimony);

        $validated = $request->validate([
            'status' => ['required', Rule::in([TestimonyStatus::Approved->value, TestimonyStatus::Rejected->value, TestimonyStatus::Revision->value])],
            'note' => [Rule::requiredIf(fn () => $request->input('status') !== TestimonyStatus::Approved->value), 'nullable', 'string', 'max:1000'],
        ], [], ['note' => 'catatan']);

        $status = TestimonyStatus::from($validated['status']);

        $testimony->forceFill([
            'status' => $status,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
            'moderation_note' => $validated['note'] ?? $testimony->moderation_note,
            'is_featured' => $status === TestimonyStatus::Approved && $testimony->is_featured,
        ])->save();

        return back()->with('status', match ($status) {
            TestimonyStatus::Approved => "\"{$testimony->title}\" disetujui dan tampil di halaman Kesaksian.",
            TestimonyStatus::Rejected => "\"{$testimony->title}\" ditolak.",
            default => "\"{$testimony->title}\" ditandai perlu revisi.",
        });
    }

    public function feature(Request $request, Testimony $testimony): RedirectResponse
    {
        Gate::authorize('moderate', $testimony);
        abort_unless($testimony->status === TestimonyStatus::Approved, 409);

        $testimony->forceFill(['is_featured' => $request->boolean('featured')])->save();

        return back()->with('status', $testimony->is_featured
            ? "\"{$testimony->title}\" tampil di Home."
            : "\"{$testimony->title}\" tidak lagi tampil di Home.");
    }

    public function destroy(Testimony $testimony): RedirectResponse
    {
        Gate::authorize('moderate', $testimony);

        $testimony->delete();

        return back()->with('status', 'Kesaksian dihapus.');
    }
}
