<?php

namespace App\Modules\Landing;

use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\SiteSection;
use App\Modules\Landing\Support\SeoMeta;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewInstance;

class LandingServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'landing';
    }

    public function name(): string
    {
        return 'Landing Page';
    }

    public function navigation(): array
    {
        return [
            ['title' => 'Landing Page', 'route' => 'landing.index', 'icon' => 'index', 'permission' => 'landing.manage', 'group' => 'Konten', 'order' => 10],
        ];
    }

    public function permissions(): array
    {
        return [
            'landing.view',
            'landing.manage',
        ];
    }

    public function seeders(): array
    {
        return [
            LandingSeeder::class,
        ];
    }

    public function sitemap(): iterable
    {
        $sections = SiteSection::query()->visible()->get()->keyBy('key')->toBase();

        yield ['loc' => route('home'), 'lastmod' => $sections->max('updated_at')];

        // A page is listed only when it has something to show (else it 404s).
        $pages = ['landing.about' => ['about', 'mission'], 'landing.schedule' => ['schedule'], 'landing.contact' => ['contact'], 'landing.privacy' => ['privacy']];

        foreach ($pages as $route => $keys) {
            $visible = $sections->only($keys);

            if ($visible->isNotEmpty()) {
                yield ['loc' => route($route), 'lastmod' => $visible->max('updated_at')];
            }
        }
    }

    protected function bootModule(): void
    {
        $this->loadViewsFrom($this->path('resources/views'), 'landing');

        // Head tags for the root view. Other modules describe their public
        // pages with ->withViewData('seo', [...]); see SeoMeta.
        View::composer('app', function (ViewInstance $view): void {
            $data = $view->getData();
            $seo = $this->app->make(SeoMeta::class)->build(
                (string) ($data['page']['component'] ?? ''),
                (array) ($data['seo'] ?? []),
            );

            $view->with(['seoTitle' => $seo['title'], 'seoHead' => $seo['head']]);
        });
    }
}
