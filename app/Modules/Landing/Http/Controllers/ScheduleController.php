<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landing\Models\ServiceSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Worship schedule (PRD 4.3 "Jadwal per kategori"): Ibadah Raya, Ibadah
 * Komisi and Menara Doa, shown on Home and the Jadwal page.
 */
class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('landing/schedule', [
            'schedules' => ServiceSchedule::query()
                ->orderByRaw('case category '.$this->categoryOrder().' end')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (ServiceSchedule $schedule) => [
                    'id' => $schedule->id,
                    'category' => $schedule->category,
                    'name' => $schedule->name,
                    'day' => $schedule->day,
                    'startTime' => substr($schedule->start_time, 0, 5),
                    'location' => $schedule->location,
                    'isActive' => $schedule->is_active,
                ])
                ->values(),
            'categories' => collect(ServiceSchedule::CATEGORIES)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'days' => collect(ServiceSchedule::DAYS)
                ->map(fn (string $label, int $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['sort_order'] = (int) ServiceSchedule::query()->where('category', $validated['category'])->max('sort_order') + 10;

        ServiceSchedule::query()->create($validated);

        return back()->with('status', "Jadwal \"{$validated['name']}\" ditambahkan.");
    }

    public function update(Request $request, ServiceSchedule $schedule): RedirectResponse
    {
        $schedule->update($this->validated($request));

        return back()->with('status', "Jadwal \"{$schedule->name}\" tersimpan.");
    }

    public function destroy(ServiceSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return back()->with('status', "Jadwal \"{$schedule->name}\" dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(ServiceSchedule::CATEGORIES))],
            'name' => ['required', 'string', 'max:120'],
            'day' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ], [], [
            'category' => 'kategori',
            'name' => 'nama kegiatan',
            'day' => 'hari',
            'start_time' => 'jam mulai',
            'location' => 'lokasi',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    private function categoryOrder(): string
    {
        return collect(array_keys(ServiceSchedule::CATEGORIES))
            ->map(fn (string $category, int $index) => "when '{$category}' then {$index}")
            ->implode(' ');
    }
}
