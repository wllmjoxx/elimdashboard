<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landing\Models\SiteSection;
use App\Modules\Landing\Support\SectionEditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing layout and content (PRD 4.8 "pengatur layout landing page"):
 * section order, visibility, and the text of each section.
 */
class SectionController extends Controller
{
    public function index(Request $request): Response
    {
        $sections = SiteSection::query()
            ->whereIn('key', SiteSection::KEYS)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SiteSection $section) => [
                'key' => $section->key,
                'label' => SectionEditor::label($section->key),
                'place' => SectionEditor::PLACES[$section->key] ?? null,
                'isActive' => $section->is_active,
                'editor' => match (true) {
                    in_array($section->key, SectionEditor::EDITABLE, true) => 'section',
                    in_array($section->key, SiteSection::SEGMENT_KEYS, true) => 'segment',
                    default => null,
                },
            ])
            ->values();

        return Inertia::render('landing/index', [
            'sections' => $sections,
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function edit(Request $request, string $section): Response
    {
        $model = SiteSection::query()->where('key', $section)->firstOrFail();

        return Inertia::render('landing/section', [
            'section' => [
                'key' => $model->key,
                'label' => SectionEditor::label($model->key),
                'place' => SectionEditor::PLACES[$model->key] ?? null,
                'content' => array_intersect_key($model->content, array_flip(SectionEditor::fields($model->key))),
            ],
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        $model = SiteSection::query()->where('key', $section)->firstOrFail();

        $validated = $request->validate(
            SectionEditor::rules($section),
            ['regex' => 'Tautan harus diawali "/", "#", atau "https://".', 'whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, +, -, dan kurung.'],
            SectionEditor::attributes(),
        );

        $validated = $this->clean($validated);

        $model->content = [...$model->content, ...$validated];
        $model->save();

        return back()->with('status', SectionEditor::label($section).' tersimpan.');
    }

    /**
     * Save a new section order (drag and drop or the arrows).
     */
    public function reorder(Request $request): RedirectResponse
    {
        $keys = $request->validate([
            'keys' => ['required', 'array'],
            'keys.*' => ['required', 'string', Rule::in(SiteSection::KEYS)],
        ])['keys'];

        DB::transaction(function () use ($keys) {
            foreach (array_values(array_unique($keys)) as $position => $key) {
                SiteSection::query()->where('key', $key)->update(['sort_order' => ($position + 1) * 10]);
            }
        });

        return back()->with('status', 'Urutan section tersimpan.');
    }

    public function visibility(Request $request, string $section): RedirectResponse
    {
        $model = SiteSection::query()->where('key', $section)->firstOrFail();
        $model->is_active = $request->validate(['is_active' => ['required', 'boolean']])['is_active'];
        $model->save();

        return back()->with('status', SectionEditor::label($section).($model->is_active ? ' ditampilkan.' : ' disembunyikan.'));
    }

    /**
     * Drop empty optional values and empty button pairs so the public pages
     * do not render blank pieces.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function clean(array $validated): array
    {
        foreach (['primary_cta', 'secondary_cta'] as $cta) {
            if (array_key_exists($cta, $validated) && blank($validated[$cta]['label'] ?? null)) {
                $validated[$cta] = null;
            }
        }

        if (isset($validated['slides'])) {
            $validated['slides'] = array_values(array_map(
                fn (array $slide) => array_filter($slide, fn ($value) => filled($value)),
                $validated['slides'],
            ));
        }

        if (array_key_exists('milestones', $validated)) {
            $validated['milestones'] = array_values($validated['milestones'] ?? []);
        }

        if (isset($validated['points'])) {
            $validated['points'] = array_values($validated['points']);
        }

        if (isset($validated['paragraphs'])) {
            $validated['paragraphs'] = array_values(array_filter($validated['paragraphs'], 'filled'));
        }

        return $validated;
    }
}
