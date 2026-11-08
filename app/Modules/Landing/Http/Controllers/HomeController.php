<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landing\Models\ServiceSchedule;
use App\Modules\Landing\Models\SiteSection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Public landing page. Sections and schedule come from the database so
     * Admin can change them without a developer.
     */
    public function __invoke(): Response
    {
        $sections = SiteSection::query()->visible()->get()
            ->map(fn (SiteSection $section) => [
                'key' => $section->key,
                'content' => $section->content,
            ])
            ->values();

        $schedules = ServiceSchedule::query()->visible()->get()->groupBy('category');

        $scheduleGroups = collect(ServiceSchedule::CATEGORIES)
            ->filter(fn (string $label, string $category) => $schedules->has($category))
            ->map(fn (string $label, string $category) => [
                'category' => $category,
                'label' => $label,
                'items' => $schedules[$category]->map(fn (ServiceSchedule $schedule) => [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'day' => $schedule->dayName(),
                    'time' => $schedule->time(),
                    'location' => $schedule->location,
                ])->values(),
            ])
            ->values();

        return Inertia::render('landing/public/home', [
            'sections' => $sections,
            'scheduleGroups' => $scheduleGroups,
        ]);
    }
}
